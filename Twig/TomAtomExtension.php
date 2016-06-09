<?php

namespace TomAtom\AtomBundle\Twig;

use \Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use \Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;

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

    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(ObjectManager $em, AuthorizationChecker $ac, KernelInterface $kernelInterface)
    {
        $this->em = $em;
        $this->ac = $ac;
        $this->kernel = $kernelInterface;
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

    /**
     *
     * @return KernelInterface
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    public function getTokenParsers()
    {
        return array(
            new TokenParserAtom(),
            new TokenParserAtomLine(),
        );
    }

    public function getName()
    {
        return 'tom_atom_extension';
    }
}