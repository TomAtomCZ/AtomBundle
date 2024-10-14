<?php

namespace TomAtom\AtomBundle\Twig;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use TomAtom\AtomBundle\Services\NodeHelper;
use Twig\Extension\AbstractExtension;

class TomAtomExtension extends AbstractExtension
{
    protected EntityManager $entityManager;
    protected AuthorizationChecker $authorizationChecker;
    protected KernelInterface $kernel;
    protected NodeHelper $nodeHelper;
    protected AtomNodeVisitor $atomNodeVisitor;

    public function __construct(EntityManager $entityManager, AuthorizationChecker $authorizationChecker, KernelInterface $kernelInterface, NodeHelper $nh)
    {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->kernel = $kernelInterface;
        $this->nodeHelper = $nh;
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function getAuthorizationChecker(): AuthorizationChecker
    {
        return $this->authorizationChecker;
    }

    public function getKernel(): KernelInterface
    {
        return $this->kernel;
    }

    public function getTokenParsers(): array
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
        return !empty($this->atomNodeVisitor) ? $this->atomNodeVisitor : $this->atomNodeVisitor = new AtomNodeVisitor($this->nodeHelper);
    }

    public function getName(): string
    {
        return 'tom_atom_extension';
    }
}
