<?php

namespace TomAtom\AtomBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use TomAtom\AtomBundle\Entity\Atom;
use Twig\Environment;
use Twig\Source;

class Template extends \Twig\Template
{
    protected EntityManagerInterface $entityManager;
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected KernelInterface $kernel;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $taExt = $env->getExtension(TomAtomExtension::class);

        $this->entityManager = $taExt->getEntityManager();
        $this->authorizationChecker = $taExt->getAuthorizationChecker();
        $this->kernel = $taExt->getKernel();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function checkAtom($name, $body)
    {
        $env = $this->kernel->getEnvironment();

        if ($env === 'prod') {
            $atom = $this->entityManager->getRepository(Atom::class)
                ->findOneBy(array('name' => $name));

            if (!$atom) {
                $atom = new Atom();
                $atom->setName($name);
                $atom->setBody($body);
                $this->entityManager->persist($atom);
                $this->entityManager->flush();
            } else {
                $body = $atom->getBody();
            }

            if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') && $this->authorizationChecker->isGranted('ROLE_ATOM_EDIT')) {
                $result = '<div class="atom" id="' . $name . '">';
                $result .= $body;
                $result .= '</div>';
            } else {
                $result = $body;
            }
        } else {
            // we are in `dev` or `test` environment: we want to bypass Atom persisting and loading.
            $result = $body;
        }

        return $result;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function checkAtomLine($name, $body)
    {
        $env = $this->kernel->getEnvironment();

        if ($env === 'prod') {
            $atom = $this->entityManager->getRepository(Atom::class)
                ->findOneBy(array('name' => $name));

            if (!$atom) {
                $atom = new Atom();
                $atom->setName($name);
                $atom->setBody($body);
                $this->entityManager->persist($atom);
                $this->entityManager->flush();
            } else {
                $body = $atom->getBody();
            }

            if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') && $this->authorizationChecker->isGranted('ROLE_ATOM_EDIT')) {
                $result = '<span class="atomline" id="' . $name . '">';
                $result .= $body;
                $result .= '</span>';
            } else {
                $result = $body;
            }
        } else {
            // we are in `dev` or `test` environment: we want to bypass Atom persisting and loading.
            $result = $body;
        }

        return $result;
    }

    public function checkAtomEntity($name, $method, $id, $body)
    {
        $env = $this->kernel->getEnvironment();

        if ($env === 'prod') {
            $prop = str_ireplace('get', '', str_ireplace('set', '', $method));

            $atom = $this->entityManager->getRepository($name)->find($id);

            if (!$atom) {
                $result = $body;
            } else {
                $body = call_user_func([$atom, 'get' . $prop]);
            }

            if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') && $this->authorizationChecker->isGranted('ROLE_ATOM_EDIT')) {
                $result = '<div class="atomentity" data-atom-entity="' . $name . '" data-atom-id="' . $id . '" data-atom-method="' . $method . '">';
                $result .= $body;
                $result .= '</div>';
            } else {
                $result = $body;
            }
        } else {
            // we are in `dev` or `test` environment: we want to bypass Atom persisting and loading.
            $result = $body;
        }

        return $result;
    }

    // must be implemented, will be overloaded in the final template
    public function getTemplateName(): string
    {
        return '';
    }

    public function getDebugInfo(): array
    {
        return [];
    }

    protected function doDisplay(array $context, array $blocks = array()): iterable
    {
        return [];
    }

    public function getSourceContext(): Source
    {
        return new Source('', $this->getTemplateName());
    }
}
