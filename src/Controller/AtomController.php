<?php

namespace TomAtom\AtomBundle\Controller;

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
use Symfony\Contracts\Cache\ItemInterface;
use TomAtom\AtomBundle\Entity\Atom;
use Twig\Extra\Cache\CacheRuntime;

class AtomController extends AbstractController
{
    public function __construct(private readonly CacheRuntime $cache, private readonly EntityManagerInterface $entityManager, private readonly AuthorizationChecker $authorizationChecker, private readonly ParameterBagInterface $parameterBag)
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
            $atom->translate($request->getLocale(), false)->setBody($request->request->get('editabledata'));
            //$atom->setCurrentLocale($request->getLocale());
            $atom->mergeNewTranslations();

//            $translation = new AtomTranslation();
//            $translation->setBody($request->request->get('editabledata'));
//            $translation->setTranslatable($atom);
//            $translation->setLocale($request->getLocale());
//            $this->entityManager->persist($translation);

            $this->entityManager->flush();

            $cacheKey = $atom->getName() . '_' . $request->getLocale();

            $this->cache->getCache()->delete($cacheKey);
            $cached = $this->cache->getCache()->get($cacheKey, function (ItemInterface $item) use ($atom, $atomType, $request) {
                //$item->tag("atom_text");


                return '<div class="' . $atomType . '" id="' . $atom->getName() . '">' . $atom->translate($request->getLocale())->getBody() . '</div>';
            });

            //dd($this->cache->getCache()->delete($atom->getName()));
//            $cache = new FilesystemTagAwareAdapter();
//            $cachedAtom = $cache->getItem($atom->getName());
//
//dd($cachedAtom);
//            $fs = new Filesystem();
//            $fs->remove($this->container->getParameter('kernel.cache_dir'));
//            $cacheDriverArr = new \Doctrine\Common\Cache\ArrayCache();
//            $cacheDriverArr->deleteAll();
//            if (function_exists('apcu_fetch')) {
//                $cacheDriverApc = new \Doctrine\Common\Cache\ApcuCache();
//                $cacheDriverApc->deleteAll();
//            }
//            if (class_exists('Memcache')) {
//                $cacheDriverMem = new \Doctrine\Common\Cache\MemcachedCache();
//                $cacheDriverMem->deleteAll();
//            }
//            nope TODO resolve how to clear old data from cache..
//            $cacheDriver = $entityManager->getConfiguration()->getResultCacheImpl();
//            $cacheDriver->deleteAll(); // to delete all cache entries $cacheDriver->deleteAll();
        } catch (Exception|InvalidArgumentException $e) {
        }

        return new JsonResponse([
            'status' => 'ok'
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

        return new JsonResponse([
            'status' => 'ok'
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
