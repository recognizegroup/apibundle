<?php
namespace Recognize\ApiBundle\Tests\DependencyInjection;

use Recognize\ApiBundle\DependencyInjection\RecognizeApiExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RecognizeApiExtensionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var RecognizeApiExtension
     */
    private $extension;

    /**
     * Root name of the configuration
     *
     * @var string
     */
    private $root;

    public function setUp() {
        parent::setUp();

        $this->extension = new RecognizeApiExtension();
        $this->root = "recognize_api";
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testConfigMustHaveJsonSchemaSet() {
        $this->extension->load(array(), $container = $this->getEmptyContainer());
    }

    public function testAlias(){
        $this->extension = new RecognizeApiExtension();

        $this->assertEquals( "recognize_api", $this->extension->getAlias() );
    }

    public function getEmptyContainer(){
        $container = new ContainerBuilder();
        $container->setParameter('recognize_api.config', array());
        return $container;
    }

}