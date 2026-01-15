<?php

namespace TomAtom\AtomBundle\Controller;

use DeepL\DeepLException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use TomAtom\AtomBundle\Entity\Atom;
use TomAtom\AtomBundle\Services\DeepLService;
use TomAtom\AtomBundle\Services\NodeHelper;

class AtomController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager,
                                private readonly AuthorizationChecker   $authorizationChecker,
                                private readonly ParameterBagInterface  $parameterBag,
                                private readonly DeepLService           $deepL,
                                private readonly NodeHelper             $nodeHelper)
    {
    }

    #[IsGranted('ROLE_ATOM_EDIT')]
    #[Route(path: '/{_locale}/save', name: 'atom_save')]
    public function saveAction(Request $request): JsonResponse
    {
        $atomName = $request->request->get('editorID');
        $atomType = $request->request->get('atomType');
        if (!$atomType) {
            $atomType = 'atom';
        }

        if (!$atomName) {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'The Atom name is not specified'
            ]);
        }

        /** @var Atom $atom */
        $atom = $this->entityManager->getRepository(Atom::class)
            ->findOneBy(['name' => $atomName]);

        if (!$atom) {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'The Atom does not exist'
            ]);
        }

        try {
            $text = $request->request->get('editabledata');
            $currentLocale = $request->getLocale();
            $defaultLocale = $request->getDefaultLocale();
            $enabledLocales = $this->parameterBag->get('kernel.enabled_locales') ?? [$defaultLocale];

            // Translate atom for current locale
            $atom->translate($currentLocale, false)->setBody($text);

            // Translate for all enabled locales if automatic translations are on and we were editing atom in default locale
            if ((int)$this->parameterBag->get('tom_atom_atom.automatic_translations') === 1 && $currentLocale === $defaultLocale) {
                foreach ($enabledLocales as $locale) {
                    if ($locale === $currentLocale) {
                        continue;
                    }
                    try {
                        $translated = $this->deepL->translate($text, $defaultLocale, $locale);
                        $atom->translate($locale, false)->setBody($translated);
                    } catch (DeepLException $e) {
                        $details = 'DeepL automatic translation did not succeed: ' . (str_ends_with($e->getMessage(), ', ') ? rtrim($e->getMessage(), ', ') : $e->getMessage());
                    }
                }
            }

            // Save translations to db
            $atom->mergeNewTranslations();
            $this->entityManager->flush();

            // Refresh cache
            $this->nodeHelper->prepareCache($atom, $atomType);
        } catch (Exception|InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'details' => $e->getMessage()
            ]);
        }

        return new JsonResponse([
            'status' => 'ok',
            'details' => $details ?? null
        ]);
    }

    #[IsGranted('ROLE_ATOM_EDIT')]
    #[Route(path: '/{_locale}/save-entity', name: 'atom_entity_save')]
    public function saveCustomEntityAction(Request $request): JsonResponse
    {
        $entityName = $request->request->get('entity');
        $entityMethod = $request->request->get('method');
        $entityId = $request->request->get('id');
        $content = $request->request->get('content');

        if (!$entityName || !$entityMethod || !$entityId) {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'Wrong or null entity data'
            ]);
        }

        $object = $this->entityManager->getRepository($entityName)->findOneBy([
            'id' => $entityId
        ]);

        if (!$object) {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'The entity ' . $entityName . ' with id ' . $entityId . ' does not exist'
            ]);
        }

        call_user_func([$object, $entityMethod], $content);

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        // Get updated content (in case there is some transformation in setter/getter)
        $prop = str_ireplace('get', '', str_ireplace('set', '', $entityMethod));
        $getProp = (str_starts_with($entityMethod, 'get') || str_starts_with($entityMethod, 'is')) ? $entityMethod : 'get' . ucfirst($prop);

        if (method_exists($object, $getProp)) {
            $updatedContent = call_user_func([$object, $getProp]);
        } else {
            $updatedContent = call_user_func([$object, 'get' . ucfirst($prop)]);
        }

        return new JsonResponse([
            'status' => 'ok',
            'content' => $updatedContent
        ]);
    }

    #[IsGranted('ROLE_ATOM_EDIT')]
    #[Route(path: '/{_locale}/atom-upload-image', name: 'atom_upload_image')]
    public function uploadImageAction(Request $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('upload');

        $fileName = $file->getClientOriginalName() . '_' . md5(uniqid()) . '.' . $file->guessExtension();
        $uploadDir = $this->parameterBag->get('kernel.project_dir') . '/web/uploads/atom';
//        $assetDirUrl = $request->getUriForPath('/uploads/atom/');
        $assetDirUrl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . '/uploads/atom';


        $file->move(
            $uploadDir,
            $fileName
        );

        return new JsonResponse([
            "uploaded" => 1,
            "fileName" => $fileName,
            "url" => $assetDirUrl . '/' . $fileName,

        ]);
    }

    #[IsGranted('ROLE_ATOM_EDIT')]
    #[Route(path: '/{_locale}/atom-image-list', name: 'atom_image_list')]
    public function imageListAction(): JsonResponse
    {
        $uploadDir = $this->parameterBag->get('kernel.project_dir') . '/web/uploads/atom';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $allImages = [];
        $finder = new Finder();

        $files = $finder->files()->in($uploadDir);
        foreach ($files as $file) {
            $allImages[] = [
                'image' => '/uploads/atom/' . $file->getFilename()
            ];
        }

        return new JsonResponse($allImages);
    }

    #[IsGranted('ROLE_ATOM_EDIT')]
    #[Route(path: '/{_locale}/atom-toggle', name: 'atom_toggle_enabled')]
    public function atomsToggleAction(Request $request): JsonResponse
    {
        $enabled = $request->request->get('enabled');
        if ($enabled && $enabled !== 'false') {
            $enabled = false;
        } else {
            $enabled = true;
        }
        return new JsonResponse([
            'status' => 'ok',
            'details' => $enabled,
        ]);
    }

    public function _metasAction(): Response
    {
        $editable = $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') && $this->authorizationChecker->isGranted('ROLE_ATOM_EDIT');

        return $this->render('@TomAtomAtom/atom/_metas.html.twig',
            ['editable' => $editable]
        );
    }
}
