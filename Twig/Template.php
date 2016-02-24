<?php

namespace TomAtom\AtomBundle\Twig;

use TomAtom\AtomBundle\Entity\Atom;
use \Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use \Doctrine\Common\Persistence\ObjectManager;

abstract class Template extends \Twig_Template
{
    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var AuthorizationChecker
     */
    protected $ac;

    public function __construct(\Twig_Environment $env)
    {
        parent::__construct($env);

        $taExt = $env->getExtension('tom_atom_extension');

        $this->em = $taExt->getEntityManager();
        $this->ac = $taExt->getAuthorizationChecker();
    }

    public function checkAtom($name, $body)
    {
        $atom = $this->em->getRepository('TomAtomAtomBundle:Atom')
            ->findOneBy(array('name' => $name));

        if(!$atom)
        {
            $atom = new Atom();
            $atom->setName($name);
            $atom->setBody($body);
            $this->em->persist($atom);
            $this->em->flush();
        }
        else
        {
            $body = $atom->getBody();
        }

        if($this->ac->isGranted('IS_AUTHENTICATED_FULLY') && $this->ac->isGranted('ROLE_SUPER_ADMIN'))
        {
            $result = '<div class="atom" id="'.$name.'">';
            $result .= $body;
            $result .= '</div>';
        }
        else
        {
            $result = $body;
        }

        return $result;
    }
}
