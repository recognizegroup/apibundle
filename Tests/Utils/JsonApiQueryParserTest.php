<?php

namespace Recognize\ApiBundle\EventListener;

use Doctrine\ORM\EntityManager;
use PHPUnit_Framework_TestCase;
use Recognize\ApiBundle\Tests\Mocks\MockEntityManager;
use Recognize\ApiBundle\Utils\JsonApiQueryBuilder;
use Recognize\ApiBundle\Utils\JsonApiQueryParser;
use Recognize\ApiBundle\Utils\JsonApiRequestBag;
use Recognize\ApiBundle\Validator\JsonApiSchemaValidator;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class JsonApiQueryParserTest extends PHPUnit_Framework_TestCase
{

    public function testSortRetrieval(){
        $bag = new ParameterBag(
            array(
                "sort" => "field1,-field2"
            )
        );

        $container = JsonApiQueryParser::parseParameterBag( $bag );
        $this->assertEquals( array(
            "sort" => array(
                "field1" => "DESC",
                "field2" => "ASC"
            )
        ), $container );
    }

    public function testFilterExact(){
        $bag = new ParameterBag(
            array(
                "filter" =>array(
                    "name" => array(
                        "eq" => "Test"
                    )
                )
            )
        );

        $container = JsonApiQueryParser::parseParameterBag( $bag );
        $this->assertEquals( array(
            "eq" => array( 'name' => 'Test')
        ), $container );
    }

    public function testFilterRange(){
        $bag = new ParameterBag(
            array(
                "filter" =>array(
                    "price" => array(
                        "range" => "0,100"
                    )
                )
            )
        );

        $container = JsonApiQueryParser::parseParameterBag( $bag );
        $this->assertEquals( array(
            "range" => array( 'price' => array( 0, 100 ) )
        ), $container );
    }

    public function testFilterIn(){
        $bag = new ParameterBag(
            array(
                "filter" =>array(
                    "name" => array(
                        "in" => "Test,Moretest"
                    )
                )
            )
        );

        $container = JsonApiQueryParser::parseParameterBag( $bag );
        $this->assertEquals( array(
            "in" => array( 'name' => array( "Test", "Moretest" ) )
        ), $container );
    }

    public function testFilterSearch(){
        $bag = new ParameterBag(
            array(
                "filter" =>array(
                    "name" => array(
                        "search" => "test"
                    )
                )
            )
        );

        $container = JsonApiQueryParser::parseParameterBag( $bag );
        $this->assertEquals( array(
            "search" => array( 'name' => "test" )
        ), $container );
    }



    public function testIncludes(){
        $bag = new ParameterBag(
            array(
                "include" => "field3,field3.name"
            )
        );

        $container = JsonApiQueryParser::parseParameterBag( $bag );
        $this->assertEquals( array(
            "joins" => array(
                array('field3', 'entity.field3'),
                array('field3_name', 'entity.field3.name')
            )
        ), $container );
    }

}
