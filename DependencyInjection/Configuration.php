<?php
namespace Recognize\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder,
    Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Yaml\Parser;

/**
 * Class Configuration
 *
 * @package Mapxact\DefaultBundle\DependencyInjection
 * @author Kevin te Raa <k.teraa@recognize.nl>
 */
class Configuration implements ConfigurationInterface {

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('recognize_api');
        $rootNode
            ->children()
                ->scalarNode("schema_directory")->isRequired()->end()
                ->arrayNode('definitions')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('path')
                                ->defaultNull()
                                ->example('/path/from/the/schema/directory.json')
                            ->end()
                            ->scalarNode('property')
                                ->defaultNull()
                                ->example('definitions')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

}