<?php

namespace TomAtom\AtomBundle\Twig;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use TomAtom\AtomBundle\Services\NodeHelper;
use Twig\Extension\AbstractExtension;

class TomAtomExtension extends AbstractExtension
{
    protected EntityManagerInterface $entityManager;
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected KernelInterface $kernel;
    protected NodeHelper $nodeHelper;
    protected AtomNodeVisitor $atomNodeVisitor;

    public function __construct(EntityManagerInterface        $entityManager,
                                AuthorizationCheckerInterface $authorizationChecker,
                                KernelInterface               $kernelInterface,
                                NodeHelper                    $nodeHelper)
    {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->kernel = $kernelInterface;
        $this->nodeHelper = $nodeHelper;
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function getAuthorizationChecker(): AuthorizationCheckerInterface
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
