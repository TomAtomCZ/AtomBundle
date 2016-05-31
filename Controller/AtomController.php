<?php

namespace TomAtom\AtomBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AtomController extends Controller
{
    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @Route("/save", name="atom_save")
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

        $atom->setBody($request->request->get('editabledata'));

        $em->persist($atom);
        $em->flush();

        return new JsonResponse([
            'status' => 'ok'
        ]);
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @Route("/save-entity", name="atom_entity_save")
     */
    public function saveCustomEntityAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $object = $em->getRepository($request->get('entity'))
            ->findOneBy(array('id' => $request->get('id')));

        if(!$object)
        {
            return new JsonResponse([
                'status' => 'error',
                'details' => 'The Atom does not exist'
            ]);
        }

        call_user_func(array($object, $request->get('method')), $request->get('html'));

        $em->persist($object);
        $em->flush();

        return new JsonResponse([
            'status' => 'ok'
        ]);
    }

    /**
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     * @Route("/atom-upload-image", name="atom_upload_image")
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
     * @Template()
     */
    public function _metasAction()
    {
        $securityContext = $this->container->get('security.authorization_checker');
        $editable = $securityContext->isGranted('IS_AUTHENTICATED_FULLY') && $securityContext->isGranted('ROLE_SUPER_ADMIN');
        return array('editable' => $editable);
    }
}