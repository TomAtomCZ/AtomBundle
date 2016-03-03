<?php

namespace TomAtom\AtomBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

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
     * @Template()
     */
    public function _metasAction()
    {
        $securityContext = $this->container->get('security.authorization_checker');
        $editable = $securityContext->isGranted('IS_AUTHENTICATED_FULLY') && $securityContext->isGranted('ROLE_SUPER_ADMIN');
        return array('editable' => $editable);
    }
}