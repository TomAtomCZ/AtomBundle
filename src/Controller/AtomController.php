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
use TomAtom\AtomBundle\Services\AtomEntitySigner;
use TomAtom\AtomBundle\Services\DeepLService;
use TomAtom\AtomBundle\Services\NodeHelper;

class AtomController extends AbstractController
{
    /**
     * CSRF token id of all requests changing data, the token is rendered in _metas.html.twig
     */
    public const CSRF_TOKEN_ID = 'atom_edit';

    /**
     * Image types which can be uploaded (detected from the file content) and the extensions they are saved with
     */
    private const UPLOAD_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/bmp' => 'bmp',
        'image/x-ms-bmp' => 'bmp',
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager,
                                private readonly AuthorizationChecker   $authorizationChecker,
                                private readonly ParameterBagInterface  $parameterBag,
                                private readonly DeepLService           $deepL,
                                private readonly NodeHelper             $nodeHelper,
                                private readonly AtomEntitySigner       $entitySigner)
    {
    }

    #[IsGranted('ROLE_ATOM_EDIT')]
    #[Route(path: '/{_locale}/save', name: 'atom_save', methods: ['POST'])]
    public function saveAction(Request $request): JsonResponse
    {
        if ($csrfError = $this->getCsrfError($request)) {
            return new JsonResponse([
                'status' => 'error',
                'details' => $csrfError
            ], Response::HTTP_FORBIDDEN);
        }

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
    #[Route(path: '/{_locale}/save-entity', name: 'atom_entity_save', methods: ['POST'])]
    public function saveCustomEntityAction(Request $request): JsonResponse
    {
        if ($csrfError = $this->getCsrfError($request)) {
            return new JsonResponse([
                'status' => 'error',
                'details' => $csrfError
            ], Response::HTTP_FORBIDDEN);
        }

        $entityName = (string)$request->request->get('entity');
        $entityMethod = (string)$request->request->get('method');
        $entityId = (string)$request->request->get('id');
        $content = $request->request->get('content');

        if ($entityName === '' || $entityMethod === '' || $entityId === '') {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'Wrong or null entity data'
            ]);
        }

        // Only the entity, method and id rendered by an {% atomentity %} tag can be saved,
        // otherwise any public method of any entity could be called
        if (!$this->entitySigner->isValid($entityName, $entityMethod, $entityId, $request->request->get('signature'))) {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'Invalid Atom Entity signature, reload the page and try again'
            ], Response::HTTP_FORBIDDEN);
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

        if (!is_callable([$object, $entityMethod])) {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'The method ' . $entityMethod . ' of the entity ' . $entityName . ' cannot be called'
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
    #[Route(path: '/{_locale}/atom-upload-image', name: 'atom_upload_image', methods: ['POST'])]
    public function uploadImageAction(Request $request): JsonResponse
    {
        if ($csrfError = $this->getCsrfError($request)) {
            return $this->uploadError($csrfError);
        }

        $file = $request->files->get('upload');
        if (!$file instanceof UploadedFile) {
            return $this->uploadError('No file was uploaded');
        }
        if (!$file->isValid()) {
            return $this->uploadError($file->getErrorMessage());
        }

        // The type is detected from the file content, the client file name and MIME type are not trusted
        $imageInfo = @getimagesize($file->getPathname());
        $extension = self::UPLOAD_IMAGE_TYPES[$imageInfo['mime'] ?? ''] ?? null;
        if ($extension === null || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return $this->uploadError('Only JPEG, PNG, GIF, WebP and BMP images can be uploaded');
        }

        // Safe file name: only [A-Za-z0-9_-] from the original name, random suffix and the extension of the detected type
        $baseName = trim((string)preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)), '-');
        $baseName = substr($baseName, 0, 50) ?: 'image';
        $fileName = $baseName . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
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

        // Offer only images, even if some other files were uploaded before the upload validation was added
        $files = $finder->files()->in($uploadDir)->name('/\.(jpe?g|png|gif|webp|bmp)$/i');
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

        return $this->render('@TomAtomAtom/atom/_metas.html.twig', [
            'editable' => $editable,
            // Generated only for editors, so anonymous visitors do not get a session
            'atom_csrf_token' => $this->authorizationChecker->isGranted('ROLE_ATOM_EDIT') ? $this->getCsrfTokenValue() : '',
        ]);
    }

    private function getCsrfTokenValue(): string
    {
        if (!$this->container->has('security.csrf.token_manager')) {
            return '';
        }

        return $this->container->get('security.csrf.token_manager')->getToken(self::CSRF_TOKEN_ID)->getValue();
    }

    /**
     * Returns null if the request has a valid CSRF token (X-CSRF-Token header or _token field), otherwise the error
     */
    private function getCsrfError(Request $request): ?string
    {
        if (!$this->container->has('security.csrf.token_manager')) {
            return 'CSRF protection is not enabled, enable framework.csrf_protection';
        }

        $token = $request->headers->get('X-CSRF-Token') ?? $request->request->get('_token');
        if (!is_string($token) || !$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $token)) {
            return 'Invalid CSRF token, reload the page and try again';
        }

        return null;
    }

    /**
     * Error response in the format of the editor upload plugins. The status is 200 on purpose: the CKEditor 4
     * upload plugin reads the error message only from responses with the status 200.
     */
    private function uploadError(string $message): JsonResponse
    {
        return new JsonResponse([
            'uploaded' => 0,
            'error' => ['message' => $message]
        ]);
    }
}
