<?php

namespace TomAtom\AtomBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use TomAtom\AtomBundle\Entity\Atom;
use TomAtom\AtomBundle\Entity\AtomTranslation;
use Twig\Extra\Cache\CacheRuntime;

class NodeHelper
{
    protected EntityManagerInterface $entityManager;
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected KernelInterface $kernel;
    protected ParameterBagInterface $parameterBag;
    protected CacheRuntime $cache;

    public function __construct(EntityManagerInterface        $entityManager,
                                AuthorizationCheckerInterface $authorizationChecker,
                                KernelInterface               $kernelInterface,
                                ParameterBagInterface         $parameterBag,
                                CacheRuntime                  $cache)
    {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->kernel = $kernelInterface;
        $this->parameterBag = $parameterBag;
        $this->cache = $cache;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function checkAtom($name, $body, $type = 'atom', $isAdmin = null)
    {
        $env = $this->kernel->getEnvironment();

        if ($env === 'prod') {
            $atom = $this->entityManager->getRepository(Atom::class)->findOneBy(['name' => $name]);
            // If atom doesn't already exist, create new one
            if (empty($atom)) {
                $atom = new Atom();
                $atom->setName($name);
                $this->entityManager->persist($atom);
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
                if ($translation->isEmpty() && $body !== null) {
                    if (!empty($translation->getTranslatable())) {
                        // Set default body to the existing atom
                        $translation->setBody($body);
                    } else {
                        // Create a new translation for the missing locale
                        $newTranslation = new AtomTranslation();
                        $newTranslation->setLocale($locale);
                        $newTranslation->setBody($body);
                        $atom->addTranslation($newTranslation);
                        $this->entityManager->persist($newTranslation);
                    }
                }
            }

            // Persist and save to db
            $atom->mergeNewTranslations();
            $this->entityManager->persist($atom);
            $this->entityManager->flush();

            // Retrieve the body from the current locale translation
            $body = $atom->getBody();
            $this->prepareCache($atom, $type);

            if ($isAdmin === null) {
                $isAdmin = $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') && $this->authorizationChecker->isGranted('ROLE_ATOM_EDIT');
            }

            if ($isAdmin) {
                $type === 'atomline' ? $result = '<span class="' . $type . '" id="' . $name . '">' : $result = '<div class="' . $type . '" id="' . $name . '">';
                $result .= $body;
                $type === 'atomline' ? $result .= '</span>' : $result .= '</div>';
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
     * @throws InvalidArgumentException
     */
    public function checkAtomLine($name, $body, $isAdmin = null)
    {
        return $this->checkAtom($name, $body, 'atomline', $isAdmin);
    }

    public function checkAtomEntity($name, $method, $id, $body, $isAdmin = null)
    {
        $env = $this->kernel->getEnvironment();

        if ($env === 'prod') {
            $prop = str_ireplace('get', '', str_ireplace('set', '', $method));

            $atom = $this->entityManager->getRepository($name)->find($id);

            if (!$atom) {
                $result = $body;
            } else {
                $getProp = (str_starts_with($method, 'get') || str_starts_with($method, 'is')) ? $method : 'get' . ucfirst($prop);
                if (method_exists($atom, $getProp)) {
                    $body = call_user_func([$atom, $getProp]);
                } else {
                    $body = call_user_func([$atom, 'get' . ucfirst($prop)]);
                }
            }

            if ($isAdmin === null) {
                $isAdmin = $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') && $this->authorizationChecker->isGranted('ROLE_ATOM_EDIT');
            }

            if ($isAdmin) {
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

    public function getDefaultLocale(): mixed
    {
        return $this->parameterBag->get('kernel.default_locale');
    }

    protected function getAllEnabledLocales(): mixed
    {
        return $this->parameterBag->get('kernel.enabled_locales');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function prepareCache(Atom $atom, string $type): void
    {
        foreach ($atom->getTranslations() as $translation) {
            $cacheKey = $atom->getName() . '_' . $translation->getLocale();

            $this->cache->getCache()->delete($cacheKey);
            $this->cache->getCache()->get($cacheKey, function (ItemInterface $item) use ($translation) {
                return $translation->getBody();
            });
        }
    }
}
