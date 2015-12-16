<?php

/*
 * This file is part of the qandidate/symfony-json-request-transformer package.
 *
 * (c) Qandidate.com <opensource@qandidate.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Recognize\ApiBundle\Tests\EventListener;

use PHPUnit_Framework_TestCase;
use Recognize\ApiBundle\EventListener\JsonRequestTransformerListener;
use Recognize\ApiBundle\Validator\JsonApiSchemaValidator;
use Symfony\Component\HttpFoundation\Request;

class JsonRequestTransformerListenerTest extends PHPUnit_Framework_TestCase
{
    private $listener;

    public function setUp()
    {
        $validator = new JsonApiSchemaValidator( array( "schema_directory" => dirname(__DIR__) . "/schemas/" ) );
        $this->listener = new JsonRequestTransformerListener( $validator );
    }

    /**
     * @dataProvider jsonContentTypes
     */
    public function testTransformCorrectly($contentType)
    {
        $data    = array('foo' => 'bar');
        $request = $this->createRequest($contentType, json_encode($data));
        $event   = $this->createGetResponseEventMock($request);

        $this->listener->onKernelRequest($event);

        $this->assertEquals(
            $data,
            $event->getRequest()->request->all()
        );
    }

    public function testTransformJsonApi(){
        $data    = array('data' => array("type" => "test", "id" => "1") );
        $request = $this->createRequest("application/vnd.api+json", json_encode($data));
        $event   = $this->createGetResponseEventMock($request);

        $this->listener->onKernelRequest($event);

        $this->assertEquals(
            $data,
            $event->getRequest()->request->all()
        );

    }

    public function jsonContentTypes()
    {
        return array(
            array('application/json'),
            array('application/x-json')
        );
    }

    public function testInvalidJson()
    {
        $request = $this->createRequest('application/json', '{meh}');
        $event   = $this->createGetResponseEventMock($request);

        $this->listener->onKernelRequest($event);

        $this->assertEquals(400, $event->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider notJsonContentTypes
     */
    public function testInvalidContentType($contentType)
    {
        $request = $this->createRequest($contentType, 'some=body');
        $event   = $this->createGetResponseEventMock($request);

        $this->listener->onKernelRequest($event);

        $this->assertEquals($request, $event->getRequest());
    }

    public function testNotReplaceEmptyContent()
    {
        $request = $this->createRequest('application/json', '');
        $event   = $this->createGetResponseEventMock($request);

        $this->listener->onKernelRequest($event);

        $this->assertEquals($request, $event->getRequest());
    }

    public function testNotREplaceNullContent()
    {
        $request = $this->createRequest('application/json', 'null');
        $event   = $this->createGetResponseEventMock($request);

        $this->listener->onKernelRequest($event);

        $this->assertEquals($request, $event->getRequest());
    }

    public function notJsonContentTypes()
    {
        return array(
            array('application/x-www-form-urlencoded'),
            array('text/html'),
            array('text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'),
        );
    }

    private function createRequest($contentType, $body)
    {
        $request = new Request(array(), array(), array(), array(), array(), array(), $body);
        $request->headers->set('CONTENT_TYPE', $contentType);

        return $request;
    }

    private function createGetResponseEventMock(Request $request)
    {
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest'))
            ->getMock();

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        return $event;
    }
}
