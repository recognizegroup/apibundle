<?php
namespace Recognize\ApiBundle\Tests\JsonApi;

use Recognize\ApiBundle\JsonApi\JsonApiResponseBody;
use Recognize\ApiBundle\Tests\Mocks\MockResourceObject;

class JsonApiResponseBodyTest extends \PHPUnit_Framework_TestCase {

    public function testSimpleResponseBody(){
        $jsonresponse = $this->getSimpleResponseBody();

        $this->assertEquals( $this->getExpectedSimpleResponseBody(), $jsonresponse->jsonSerialize() );
    }

    public function testResponseBodyWithRelationship(){
        $jsonresponse = $this->getResponseBodyWithRelationship();

        $this->assertEquals( $this->getExpectedResponseBodyWithRelationship(),
            $jsonresponse->jsonSerialize() );
    }

    public function testResponseBodyWithMultpleRelationships(){
        $jsonresponse = $this->getResponseBodyWithMultipleRelationships();

        $this->assertEquals( $this->getExpectedResponseBodyWithMultipleRelationships(),
            $jsonresponse->jsonSerialize() );
    }

    public function testResponseBodyWithNestedRelationships(){
        $jsonresponse = $this->getResponseBodyWithNestedRelationships();

        $this->assertEquals( $this->getExpectedResponseBodyWithNestedRelationships(),
            $jsonresponse->jsonSerialize() );
    }

    public function testSimpleMetaResponseBody(){
        $jsonresponse = new JsonApiResponseBody( array(), array("test" => "test"));

        $this->assertEquals( array(
            "data" => array(),
            "meta" => array(
                "test" => "test"
            )
        ), $jsonresponse->jsonSerialize() );

    }

    protected function getExpectedSimpleResponseBody(){
        return array(
            "data" => array(
                "id" => "1",
                "type" => "test",
                "attributes" => array("name" => "test", "extra" => "nottest")
            )
        );
    }

    protected function getExpectedResponseBodyWithRelationship(){
        return array(
            "data" => array(
                "id" => "1",
                "type" => "test",
                "attributes" => array("name" => "test", "extra" => "nottest"),
                "relationships" => array(
                    "groups" => array(
                        "data" => array( "id" => "2", "type" => "test")
                    )
                )
            ),
            "included" => array(
                array(
                    "id" => "2",
                    "type" => "test",
                    "attributes" => array("name" => "relationship", "extra" => "nottest")
                )
            )
        );
    }

    protected function getExpectedResponseBodyWithMultipleRelationships(){
        return array(
            "data" => array(
                "id" => "1",
                "type" => "test",
                "attributes" => array("name" => "test", "extra" => "nottest"),
                "relationships" => array(
                    "groups" => array(
                        "data" => array( array( "id" => "2", "type" => "test") )
                    ),
                    "color" => array(
                        "data" => array( "id" => "1", "type" => "color")
                    )
                )
            ),
            "included" => array(
                array(
                    "id" => "2",
                    "type" => "test",
                    "attributes" => array("name" => "relationship", "extra" => "nottest")
                ),
                array(
                    "id" => "1",
                    "type" => "color",
                    "attributes" => array("name" => "red")
                )
            )
        );
    }

    protected function getExpectedResponseBodyWithNestedRelationships(){
        return array(
            "data" => array(
                "id" => "1",
                "type" => "test",
                "attributes" => array("name" => "test", "extra" => "nottest"),
                "relationships" => array(
                    "groups" => array(
                        "data" => array( array( "id" => "2", "type" => "test") )
                    )
                )
            ),
            "included" => array(
                array(
                    "id" => "2",
                    "type" => "test",
                    "attributes" => array("name" => "relationship", "extra" => "nottest"),
                    "relationships" => array(
                        "groups" => array(
                            "data" => array( array( "id" => "3", "type" => "test") )
                        ),
                        "color" => array(
                            "data" => array( "id" => "1", "type" => "color")
                        )
                    )
                ),
                array(
                    "id" => "3",
                    "type" => "test",
                    "attributes" => array("name" => "nestedrelationship", "extra" => "nottest")
                ),
                array(
                    "id" => "1",
                    "type" => "color",
                    "attributes" => array("name" => "red"),
                    "relationships" => array(
                        "color" => array(
                            "data" => array( "id" => "2", "type" => "color")
                        )
                    )
                ),
                array(
                    "id" => "2",
                    "type" => "color",
                    "attributes" => array("name" => "yellow")
                )
            )
        );
    }

    protected function getResponseBodyWithRelationship(){
        $relationshipresource = new MockResourceObject( 2, "test", array("name" => "relationship", "extra" => "nottest") );

        $mainresource = new MockResourceObject( 1, "test", array("name" => "test", "extra" => "nottest"),
            array( "groups" => $relationshipresource ) );

        return new JsonApiResponseBody( $mainresource );
    }

    protected function getResponseBodyWithMultipleRelationships(){
        $relationshipresource = new MockResourceObject( 2, "test", array("name" => "relationship", "extra" => "nottest") );
        $colorresource = new MockResourceObject( 1, "color", array("name" => "red") );

        $mainresource = new MockResourceObject( 1, "test", array("name" => "test", "extra" => "nottest"),
            array( "groups" => array( $relationshipresource ), "color" => $colorresource ) );

        return new JsonApiResponseBody( $mainresource );
    }

    protected function getResponseBodyWithNestedRelationships(){
        $yellowresource = new MockResourceObject( 2, "color", array("name" => "yellow") );
        $colorresource = new MockResourceObject( 1, "color", array("name" => "red"),
            array( "color" => $yellowresource ));
        $nestedrelationshipresource = new MockResourceObject( 3, "test", array("name" => "nestedrelationship", "extra" => "nottest") );
        $relationshipresource = new MockResourceObject( 2, "test", array("name" => "relationship", "extra" => "nottest"),
            array( "groups" => array( $nestedrelationshipresource ), "color" => $colorresource ) );

        $mainresource = new MockResourceObject( 1, "test", array("name" => "test", "extra" => "nottest"),
            array( "groups" => array( $relationshipresource ) ) );

        return new JsonApiResponseBody( $mainresource );
    }

    protected function getSimpleResponseBody(){
        $resource = new MockResourceObject( 1, "test", array("name" => "test", "extra" => "nottest") );

        return new JsonApiResponseBody( $resource );
    }
}
