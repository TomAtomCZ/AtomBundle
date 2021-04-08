<?php

namespace TomAtom\AtomBundle\Twig;

use \Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\KernelInterface;
use TomAtom\AtomBundle\Services\NodeHelper;
use Twig\Extension\AbstractExtension;

class TomAtomExtension extends AbstractExtension
{
    /**
     * @var EntityManager
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

    /**
     * @var NodeHelper
     */
    protected $nodeHelper;

    /**
     * @var AtomNodeVisitor
     */
    protected $atomNodeVisitor;

    public function __construct(EntityManager $em, AuthorizationChecker $ac, KernelInterface $kernelInterface, NodeHelper $nh)
    {
        $this->em = $em;
        $this->ac = $ac;
        $this->kernel = $kernelInterface;
        $this->nodeHelper = $nh;
    }

    /**
     *
     * @return EntityManager
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
        return [
            new TokenParserAtom(),
            new TokenParserAtomLine(),
            new TokenParserAtomEntity(),
        ];
    }

    public function getNodeVisitors(): array
    {
        return [$this->getTranslationNodeVisitor()];
    }

    public function getTranslationNodeVisitor(): AtomNodeVisitor
    {
        return $this->atomNodeVisitor ?: $this->atomNodeVisitor = new AtomNodeVisitor($this->nodeHelper);
    }

    public function getName()
    {
        return 'tom_atom_extension';
    }
}
