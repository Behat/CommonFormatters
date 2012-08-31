<?php

namespace Behat\CommonFormatters;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition,
    Symfony\Component\DependencyInjection\ContainerBuilder;

use Behat\Behat\Extension\ExtensionInterface;

class Extension implements ExtensionInterface
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $config    Extension configuration hash (from behat.yml)
     * @param ContainerBuilder $container ContainerBuilder instance
     */
    public function load(array $config, ContainerBuilder $container)
    {
        if (isset($config['formatters'])) {
            $container->setParameter('behat.formatter.classes', $config['formatters']);
        }
    }

    /**
     * Setups configuration for current extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function getConfig(ArrayNodeDefinition $builder)
    {
        $builder
            ->useAttributeAsKey('name')
            ->prototype('variable')
        ;
    }

    /**
     * Returns compiler passes used by this extension.
     *
     * @return array
     */
    public function getCompilerPasses()
    {
        return array();
    }
}
