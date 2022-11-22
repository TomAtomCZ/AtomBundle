<?php

namespace TomAtom\AtomBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Contracts\Cache\ItemInterface;
use TomAtom\AtomBundle\Entity\Atom;
use TomAtom\AtomBundle\Entity\AtomTranslation;
use TomAtom\AtomBundle\Utils\AtomSettings;
use Twig\Extra\Cache\CacheRuntime;

class AtomController extends AbstractController
{

    public function __construct(private CacheRuntime $cache, private AuthorizationChecker $authorizationChecker, private ParameterBagInterface $parameterBag) {

    }

    /**

     */
    #[IsGranted('ROLE_ATOM_EDIT')]
    #[Route(path: '/{_locale}/save', name: 'atom_save')]
    public function saveAction(Request $request, ManagerRegistry $doctrine)
    {
        $atomName = $request->request->get('editorID');
        $atomType = $request->request->get('atomType');
        if(!$atomType) {
            $atomType = 'atom';
        }

        if(!$atomName) {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'The Atom name is not specified'
            ]);
        }

        $em = $doctrine->getManager();

        /** @var Atom $atom */
        $atom = $em->getRepository(Atom::class)
            ->findOneBy(['name' => $atomName]);

        if(!$atom)
        {
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
//            $em->persist($translation);

            $em->flush();

            $cacheKey = $atom->getName() . '_' . $request->getLocale();

            $this->cache->getCache()->delete($cacheKey);
            $cached = $this->cache->getCache()->get($cacheKey, function (ItemInterface $item) use ($atom, $atomType, $request) {
                //$item->tag("atom_text");


                return '<div class="'.$atomType.'" id="'.$atom->getName().'">'.$atom->translate($request->getLocale())->getBody().'</div>';
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
        } catch (\Exception $e) {}

        return new JsonResponse([
            'status' => 'ok'
        ]);
    }

    /**

     */
    #[IsGranted('ROLE_ATOM_EDIT')]
    #[Route(path: '/{_locale}/save-entity', name: 'atom_entity_save')]
    public function saveCustomEntityAction(Request $request, ManagerRegistry $doctrine)
    {
        $entityName = $request->request->get('entity');
        $entityMethod = $request->request->get('method');
        $entityId = $request->request->get('id');
        $content = $request->request->get('content');
        $em = $doctrine->getManager();

        if (!$entityName || !$entityMethod || !$entityId) {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'Wrong or null entity data'
            ]);
        }

        $object = $em->getRepository($entityName)->findOneBy([
            'id' => $entityId
        ]);

        if(!$object)
        {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'The entity ' . $entityName . ' with id ' . $entityId . ' does not exist'
            ]);
        }

        call_user_func([$object, $entityMethod], $content);

        $em->persist($object);
        $em->flush();

        return new JsonResponse([
            'status' => 'ok'
        ]);
    }

    /**

     */
    #[IsGranted('ROLE_ATOM_EDIT')]
    #[Route(path: '/{_locale}/atom-upload-image', name: 'atom_upload_image')]
    public function uploadImageAction(Request $request)
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

    /**

     */
    #[IsGranted('ROLE_ATOM_EDIT')]
    #[Route(path: '/{_locale}/atom-image-list', name: 'atom_image_list')]
    public function imageListAction(Request $request)
    {
        $uploadDir = $this->parameterBag->get('kernel.project_dir') . '/web/uploads/atom';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $allImages = [];
        $finder = new Finder();

        $files = $finder->files()->in($uploadDir);
        foreach ($files as $file) {
            array_push($allImages, [
                'image' => '/uploads/atom/' . $file->getFilename()
            ]);
        }

        return new JsonResponse($allImages);
    }

    /**

     */
    #[IsGranted('ROLE_ATOM_EDIT')]
    #[Route(path: '/{_locale}/atom-toggle', name: 'atom_toggle_enabled')]
    public function atomsToggleAction(Request $request)
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

    /**
     * @Template()
     */
    public function _metasAction()
    {
        $editable = $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') && $this->authorizationChecker->isGranted('ROLE_ATOM_EDIT');

        return ['editable' => $editable];
    }
}
