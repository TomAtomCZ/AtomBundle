<?php

namespace TomAtom\AtomBundle\Twig;

use \Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use \Doctrine\Common\Persistence\ObjectManager;

class TomAtomExtension extends \Twig_Extension
{
    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var AuthorizationChecker
     */
    protected $ac;

    public function __construct(ObjectManager $em, AuthorizationChecker $ac)
    {
        $this->em = $em;
        $this->ac = $ac;
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
     * @return AuthorizationChecker
     */
    public function getAuthorizationChecker()
    {
        return $this->ac;
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