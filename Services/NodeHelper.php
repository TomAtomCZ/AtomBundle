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
use TomAtom\AtomBundle\Entity\AtomTranslation;
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

    public function checkAtom($name, $body, $type = 'atom')
    {
        $env = $this->kernel->getEnvironment();

        if ($env === 'prod') {
            $atom = $this->em->getRepository(Atom::class)->findOneBy(['name' => $name]);
            // If atom doesn't already exist, create new one
            if (empty($atom)) {
                $atom = new Atom();
                $atom->setName($name);
                $this->em->persist($atom);
            }

            // Check if the translation exists for all locales - enabled_locales must be defined in translation.yaml
            $locales = $this->getAllEnabledLocales();
            // If enabled_locales is missing fallback to get locales from the translation messages files
            $translationDir = $this->parameterBag->get('kernel.project_dir') . '/translations';
            if (empty($locales) && is_dir($translationDir)) {
                // Get the translation messages files
                $translationFiles = glob($translationDir . '/*.{yml,yaml}', GLOB_BRACE);
                // Get the locales from the translation files
                $locales = array_map(function ($filePath) {
                    $info = pathinfo($filePath);
                    return str_replace('messages.', '', $info['filename']);
                }, $translationFiles);
            }

            foreach ($locales as $locale) {
                $translation = $atom->translate($locale, false);
                if ($translation->isEmpty()) {
                    // Create a new translation for the missing locale
                    $newTranslation = new AtomTranslation();
                    $newTranslation->setLocale($locale);
                    $newTranslation->setBody($body);
                    $atom->addTranslation($newTranslation);
                    $this->em->persist($newTranslation);
                }
            }

            // Persist and save to db
            $atom->mergeNewTranslations();
            $this->em->persist($atom);
            $this->em->flush();

            // Retrieve the body from the default translation
            $body = $atom->translate($this->getDefaultLocale())->getBody();
            $this->prepareCache($atom, $type);

            if ($this->ac->isGranted('IS_AUTHENTICATED_FULLY') && $this->ac->isGranted('ROLE_ATOM_EDIT')) {
                $result = '<div class="' . $type . '" id="' . $name . '">';
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

    protected function getAllEnabledLocales() {
        return $this->parameterBag->get('kernel.enabled_locales');
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
