<?php

namespace TomAtom\AtomBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use TomAtom\AtomBundle\Utils\AtomSettings;

class AtomController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_ATOM_EDIT')")
     * @Route("/{_locale}/save", name="atom_save")
     */
    public function saveAction(Request $request)
    {
        $atomName = $request->request->get('editorID');

        if(!$atomName) {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'The Atom name is not specified'
            ]);
        }

        $em = $this->getDoctrine()->getManager();

        $atom = $em->getRepository('TomAtomAtomBundle:Atom')
            ->findOneBy(['name' => $atomName]);

        if(!$atom)
        {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'The Atom does not exist'
            ]);
        }

        try {
            $atom->setBody($request->request->get('editabledata'));
            $em->persist($atom);
            $em->flush();
//            $fs = new Filesystem();
//            $fs->remove($this->container->getParameter('kernel.cache_dir'));
            $cacheDriverArr = new \Doctrine\Common\Cache\ArrayCache();
            $cacheDriverArr->deleteAll();
            if (function_exists('apcu_fetch')) {
                $cacheDriverApc = new \Doctrine\Common\Cache\ApcuCache();
                $cacheDriverApc->deleteAll();
            }
            if (class_exists('Memcache')) {
                $cacheDriverMem = new \Doctrine\Common\Cache\MemcachedCache();
                $cacheDriverMem->deleteAll();
            }
//            nope TODO resolve how to clear old data from cache..
//            $cacheDriver = $entityManager->getConfiguration()->getResultCacheImpl();
//            $cacheDriver->deleteAll(); // to delete all cache entries $cacheDriver->deleteAll();
        } catch (\Exception $e) {}

        return new JsonResponse([
            'status' => 'ok'
        ]);
    }

    /**
     * @Security("is_granted('ROLE_ATOM_EDIT')")
     * @Route("/{_locale}/save-entity", name="atom_entity_save")
     */
    public function saveCustomEntityAction(Request $request)
    {
        $entityName = $request->request->get('entity');
        $entityMethod = $request->request->get('method');
        $entityId = $request->request->get('id');
        $content = $request->request->get('content');
        $em = $this->getDoctrine()->getManager();

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
     * @Security("is_granted('ROLE_ATOM_EDIT')")
     * @Route("/{_locale}/atom-upload-image", name="atom_upload_image")
     */
    public function uploadImageAction(Request $request)
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('upload');

        $fileName = $file->getClientOriginalName() . '_' . md5(uniqid()) . '.' . $file->guessExtension();
        $uploadDir = $this->get('kernel')->getRootDir() . '/../web/uploads/atom';
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
     * @Security("is_granted('ROLE_ATOM_EDIT')")
     * @Route("/{_locale}/atom-image-list", name="atom_image_list")
     */
    public function imageListAction(Request $request)
    {
        $uploadDir = $this->get('kernel')->getRootDir() . '/../web/uploads/atom';
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
     * @Security("is_granted('ROLE_ATOM_EDIT')")
     * @Route("/{_locale}/atom-toggle", name="atom_toggle_enabled")
     */
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
        $securityContext = $this->container->get('security.authorization_checker');
        $editable = $securityContext->isGranted('IS_AUTHENTICATED_FULLY') && $securityContext->isGranted('ROLE_ATOM_EDIT');
        return ['editable' => $editable];
    }
}
