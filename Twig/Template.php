<?php

namespace TomAtom\AtomBundle\Twig;

use TomAtom\AtomBundle\Entity\Atom;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;

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

    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(\Twig_Environment $env)
    {
        parent::__construct($env);

        $taExt = $env->getExtension('tom_atom_extension');

        $this->em = $taExt->getEntityManager();
        $this->ac = $taExt->getAuthorizationChecker();
        $this->kernel = $taExt->getKernel();
    }

    public function checkAtom($name, $body)
    {
        $env = $this->kernel->getEnvironment();

        if($env === 'prod') {
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

            if($this->ac->isGranted('IS_AUTHENTICATED_FULLY') && $this->ac->isGranted('ROLE_ATOM_EDIT'))
            {
                $result = '<div class="atom" id="'.$name.'">';
                $result .= $body;
                $result .= '</div>';
            }
            else
            {
                $result = $body;
            }
        } else {
            // we are in `dev` or `test` environment: we want to bypass Atom persisting and loading.
            $result = $body;
        }

        return $result;
    }
}
