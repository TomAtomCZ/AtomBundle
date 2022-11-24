<?php

namespace TomAtom\AtomBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Contracts\Cache\ItemInterface;
use TomAtom\AtomBundle\Entity\Atom;
use Twig\Extra\Cache\CacheRuntime;


class NodeHelper {
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
    protected ParameterBag $parameterBag;
    protected CacheRuntime $cache;

    public function __construct(EntityManager $em, AuthorizationChecker $ac, KernelInterface $kernelInterface, ParameterBag $parameterBag, CacheRuntime $cache)
    {
        $this->em = $em;
        $this->ac = $ac;
        $this->kernel = $kernelInterface;
        $this->parameterBag = $parameterBag;
        $this->cache = $cache;
    }

    public function checkAtom($name, $body, $type = 'atom') {
        $env = $this->kernel->getEnvironment();

        if($env === 'prod') {
            $atom = $this->em->getRepository(Atom::class)->findOneBy(['name' => $name]);
            if(!$atom) {
                $atom = new Atom();
                $atom->setName($name);
                $atom->translate($this->getDefaultLocale())->setBody($body);
                $atom->mergeNewTranslations();
                $this->em->persist($atom);
                $this->em->flush();
            } else {
                $body = $atom->translate($this->getDefaultLocale())->getBody();
                $this->prepareCache($atom, $type);
            }

            if($this->ac->isGranted('IS_AUTHENTICATED_FULLY') && $this->ac->isGranted('ROLE_ATOM_EDIT')) {
                $result = '<div class="'.$type.'" id="'.$name.'">';
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


    public function checkAtomLine($name, $body) {
        return $this->checkAtom($name, $body, 'atomline');
    }

    public function getDefaultLocale() {
        return $this->parameterBag->get('kernel.default_locale');
    }

    private function prepareCache(Atom $atom, string $type) {
        foreach ($atom->getTranslations() as $translation) {
            $cacheKey = $atom->getName() . '_' . $translation->getLocale();

            $this->cache->getCache()->get($cacheKey, function (ItemInterface $item) use ($atom, $translation, $type) {
                return '<div class="'.$type.'" id="'.$atom->getName().'">'.$translation->getBody().'</div>';
            });
        }
    }
}
