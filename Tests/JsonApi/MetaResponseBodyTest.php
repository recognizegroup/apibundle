<?php
namespace Recognize\ApiBundle\Tests\JsonApi;

use Recognize\ApiBundle\JsonApi\MetaResponseBody;

class MetaResponseBodyTest extends \PHPUnit_Framework_TestCase {

    public function testCorrectHeaders(){
        $metaresponse = new MetaResponseBody();

        $this->assertEquals( "application/vnd.api+json", $metaresponse->headers->get("Content-Type") );
    }
}
