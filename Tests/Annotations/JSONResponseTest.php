<?php
namespace Recognize\ApiBundle\Tests\Annotations;

use PHPUnit_Framework_TestCase;
use Recognize\ApiBundle\Annotation\JSONAPIResponse;
use Recognize\ApiBundle\Annotation\JSONResponse;
use Recognize\ApiBundle\Validator\JsonApiSchemaValidator;
use Symfony\Component\HttpFoundation\Request;

class JSONResponseTest extends PHPUnit_Framework_TestCase
{
    public function testAnnotation(){
        $annotation = new JSONResponse( array() );
        $this->assertFalse( $annotation->allowArray() );
        $this->assertEquals( "jsonresponse", $annotation->getAliasName() );
    }

}
