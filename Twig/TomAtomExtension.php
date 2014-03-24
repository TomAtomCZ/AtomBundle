<?php

namespace TomAtom\AtomBundle\Twig;

use \Symfony\Component\Security\Core\SecurityContext;
use \Doctrine\Common\Persistence\ObjectManager;

class TomAtomExtension extends \Twig_Extension
{
    /**
     * @var ObjectManager
     */
    protected $em;
    
    /**
     * @var SecurityContext
     */
    protected $sc;

    public function __construct(ObjectManager $em, SecurityContext $sc)
    {
        $this->em = $em;
        $this->sc = $sc;
    }
    
    /**
     * 
     * @return ObjectManager
     */
    public function getEntityManager() 
    {
        return $this->em;
    }
    
    /**
     * 
     * @return SecurityContext
     */
    public function getSecurityContext() 
    {
        return $this->sc;
    }
    
    public function getTokenParsers()
    {
        return array(
            new TokenParserAtom(),
        );
    }

    public function getName()
    {
        return 'tom_atom_extension';
    }
}