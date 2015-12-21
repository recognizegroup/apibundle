<?php

namespace Recognize\ApiBundle\EventListener;

use PHPUnit_Framework_TestCase;
use Recognize\ApiBundle\Utils\JsonApiRequestBag;
use Recognize\ApiBundle\Validator\JsonApiSchemaValidator;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class JsonApiRequestBagTest extends PHPUnit_Framework_TestCase
{

    public function testAttributes(){
        $request = new Request();
        $request->request = new ParameterBag( array(
            "data" => array(
                "id" => "1",
                "type" => "test",
                "attributes" => array(
                    "name" => "testnaam"
                )
            )
        )
        );
        $bag = new JsonApiRequestBag( $request );

        $this->assertEquals( "testnaam", $bag->getAttribute( "name" ) );
        $this->assertEquals( null, $bag->getAttribute("asdf") );
    }

    public function testRelationships(){
        $request = new Request();
        $request->request = new ParameterBag( array(
                "data" => array(
                    "id" => "1",
                    "type" => "test",
                    "relationships" => array(
                        "names" => array(
                            array(
                                "id" => "1",
                                "type" => "name"
                            )
                        ),
                        "phonenumber" => array(
                            "id" => "2",
                            "type" => "number"
                        )
                    )
                )
            )
        );
        $bag = new JsonApiRequestBag( $request );

        $this->assertEquals( array("1"), $bag->getRelationshipIds( "names", "name" ) );
        $this->assertEquals( array("2"), $bag->getRelationshipIds( "phonenumber" ) );
    }

    public function testTypeAndId(){
        $request = new Request();
        $request->request = new ParameterBag( array(
                "data" => array(
                    "id" => "1",
                    "type" => "test",
                    "relationships" => array(
                        "names" => array(
                            array(
                                "id" => "1",
                                "type" => "name"
                            )
                        ),
                        "phonenumber" => array(
                            "id" => "2",
                            "type" => "number"
                        )
                    )
                )
            )
        );
        $bag = new JsonApiRequestBag( $request );

        $this->assertEquals( "test", $bag->getType() );
        $this->assertEquals( "1", $bag->getId() );
    }

}
