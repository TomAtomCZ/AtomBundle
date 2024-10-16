<?php

namespace TomAtom\AtomBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class TomAtomAtomBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->booleanNode('automatic_translations')->defaultFalse()->end()
            ->scalarNode('deepl_key')->defaultValue('')->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('tom_atom_atom.automatic_translations', $config['automatic_translations']);
        $builder->setParameter('tom_atom_atom.deepl_key', $config['deepl_key']);
        $container->import('../config/services.yaml');
    }
}
