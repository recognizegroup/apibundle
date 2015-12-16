<?php
namespace Recognize\ApiBundle\Tests\Annotations;

use PHPUnit_Framework_TestCase;
use Recognize\ApiBundle\Annotation\JSONAPIResponse;
use Recognize\ApiBundle\Validator\JsonApiSchemaValidator;
use Symfony\Component\HttpFoundation\Request;

class JSONAPIResponseTest extends PHPUnit_Framework_TestCase
{
    public function testAnnotation(){
        $annotation = new JSONAPIResponse( array("value" => "testresponse.json" ) );
        $this->assertFalse( $annotation->allowArray() );
        $this->assertEquals( "jsonapi_response", $annotation->getAliasName() );
    }

}
