<?php
 namespace Recognize\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\HttpKernel\DependencyInjection\Extension,
    Symfony\Component\Config\FileLocator;

use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class Recognize\ApiBundle\DependencyInjection\RecognizeApiExtension
 * @package Recognize\ApiBundle\DependencyInjection
 * @author Kevin te Raa <k.teraa@recognize.nl>
 */
class RecognizeApiExtension extends Extension {

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container) {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->loadConfig( $container, $config );
    }

    public function loadConfig( ContainerBuilder $container, $config ){
        $container->setParameter('recognize_api.config', $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * @return string
     */
    public function getAlias() {
        return 'recognize_api';
    }

}
