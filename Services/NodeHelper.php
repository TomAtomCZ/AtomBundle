<?php

namespace TomAtom\AtomBundle\Services;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use TomAtom\AtomBundle\Entity\Atom;


class NodeHelper {
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

    public function checkAtom($name, $body) {
        $env = $this->kernel->getEnvironment();

        if($env === 'dev') {
            $atom = $this->em->getRepository(Atom::class)->findOneBy(['name' => $name]);
            if(!$atom) {
                $atom = new Atom();
                $atom->setName($name);
                $atom->setBody($body);
                $this->em->persist($atom);
                $this->em->flush();
            } else {
                $body = $atom->getBody();
            }

            if($this->ac->isGranted('IS_AUTHENTICATED_FULLY') && $this->ac->isGranted('ROLE_ATOM_EDIT')) {
                $result = '<div class="atom" id="'.$name.'">';
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

}
