<?php

namespace TomAtom\AtomBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class AtomController extends Controller
{
    /**
     * @Route("/save", name="atom_save")
     * @Template()
     */
    public function saveAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $atom = $em->getRepository('TomAtomAtomBundle:Atom')
            ->findOneBy(array('name' => $request->get('name')));

        if(!$atom)
        {
            throw $this->createNotFoundException('The atom does not exist');
        }

        $atom->setBody($request->get('body'));

        $em->persist($atom);
        $em->flush();     
        
        return new JsonResponse(array('status' => 'ok'));
    }
    
    /**
     * @Route("/save-entity", name="atom_entity_save")
     * @Template()
     */
    public function saveCustomEntityAction(Request $request)
    {
//        $logger = $this->get('logger');
//        $logger->info($request->get('name'));
//        $logger->info($request->get('body'));
        
        $em = $this->getDoctrine()->getManager();
        
        $object = $em->getRepository('TomAtomAtomBundle:'.$request->get('entity'))
            ->findOneBy(array('id' => $request->get('id')));

        if(!$object)
        {
            throw $this->createNotFoundException('The '.$request->get('entity').' does not exist');
        }

        call_user_func(array($object, $request->get('method')), $request->get('html')); 

        $em->persist($object);
        $em->flush();     
        
        return new JsonResponse(array('status' => 'ok'));
    }

    /**
     * @Template()
     */
    public function _metasAction()
    {
        $securityContext = $this->container->get('security.context');
        $editable = $securityContext->isGranted('IS_AUTHENTICATED_FULLY');
        return array('editable' => $editable);
    }
}
