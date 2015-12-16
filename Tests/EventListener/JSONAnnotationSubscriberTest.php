<?php
namespace Recognize\ApiBundle\Tests\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\FileCacheReader;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use Recognize\ApiBundle\EventListener\JSONAnnotationSubscriber;
use Recognize\ApiBundle\Tests\Mocks\MockController;
use Recognize\ApiBundle\Validator\JsonApiSchemaValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JSONAnnotationSubscriberTest extends PHPUnit_Framework_TestCase {

    /** @var  JSONAnnotationSubscriber */
    private $subscriber;

    public function setUp()
    {
        $validator = new JsonApiSchemaValidator( array( "schema_directory" => dirname(__DIR__) . "/schemas/",
            "definitions" => array( array( "path" => "names.json", "property" => "names") ) ) );

        $annotationReader = new AnnotationReader();

        $this->subscriber = new JSONAnnotationSubscriber( $annotationReader,
            new NullLogger(), $validator, "dev" );
    }

    public function testJsonResponseAnnotation( ) {
        $data    = array('foo' => 'bar');
        $request = $this->createRequest("application/json", json_encode($data));
        $event   = $this->createFilterControllerEventMock($request, "jsonAction");

        $this->subscriber->onKernelController( $event );
        $this->assertTrue( $event->getRequest()->attributes->get("_json_response") );
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testInvalidRequestJsonApiResponseAnnotation(){
        $data    = array('foo' => 'bar');
        $request = $this->createRequest("application/json", json_encode($data));
        $event   = $this->createFilterControllerEventMock($request, "jsonApiAction");

        $this->subscriber->onKernelController( $event );
    }

    /**
     * @expectedException \Doctrine\Common\Annotations\AnnotationException
     */
    public function testJsonApiResponseAnnotationWithoutSchema(){
        $data    = array('foo' => 'bar');
        $request = $this->createRequest("application/vnd.api+json", json_encode($data));
        $event   = $this->createFilterControllerEventMock($request, "schemalessJsonApiAction");

        $this->subscriber->onKernelController( $event );
    }

    public function testValidJsonApiResponseAnnotation(){
        $data    = '{"data":{"type":"test","attributes": {"name":"test"}}';
        $request = $this->createRequest("application/vnd.api+json", $data);
        $event   = $this->createFilterControllerEventMock($request, "jsonApiAction");

        $this->subscriber->onKernelController( $event );
        $this->assertTrue( $event->getRequest()->attributes->get("_json_api_response") );
    }

    public function testJsonResponseViewWithEmptyResponse(){
        $request = $this->createRequest( "application/json", '');
        $request->attributes->set("_json_response", true);
        $event = $this->createGetResponseForControllerResultEvent( $request, null );

        $this->subscriber->onKernelView( $event );
        $this->assertEquals( "", $event->getResponse()->getContent() );
    }

    public function testJsonResponseView(){
        $request = $this->createRequest( "application/json", '');
        $request->attributes->set("_json_response", true);
        $event = $this->createGetResponseForControllerResultEvent( $request, json_decode('{"simple":"json"}') );

        $this->subscriber->onKernelView( $event );
        $this->assertEquals('{"simple":"json"}' , $event->getResponse()->getContent() );
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testJsonApiResponseInvalidFormat(){
        $request = $this->createRequest( "application/vnd.api+json", '');
        $request->attributes->set("_json_api_response", true);
        $request->attributes->set("_json_schema", "testresponse.json" );
        $event = $this->createGetResponseForControllerResultEvent( $request, json_decode('{"simple":"json"}') );

        $this->subscriber->onKernelView( $event );
    }

    public function testValidJsonApiResponse(){
        $responsedata = '{"data":{"id":"1","type":"test","attributes":{"name":"test"}}}';

        $request = $this->createRequest( "application/vnd.api+json",
            "");
        $request->attributes->set("_json_api_response", true);
        $request->attributes->set("_json_schema", "testresponse.json" );
        $event = $this->createGetResponseForControllerResultEvent( $request, json_decode($responsedata) );

        $this->subscriber->onKernelView( $event );
        $this->assertEquals($responsedata , $event->getResponse()->getContent() );
    }

    public function testValidJsonApiErrorsDoesntCrash(){
        $httpexception = new HttpException("400", "TEST");
        $event = $this->createGetResponseForExceptionEvent( $httpexception, new Response() );
        $this->subscriber->onKernelException( $event );

        $event = $this->createGetResponseForExceptionEvent( $httpexception );
        $this->subscriber->onKernelException( $event );

        $exception = new \Exception("TEST", 204);
        $event = $this->createGetResponseForExceptionEvent( $exception );
        $this->subscriber->onKernelException( $event );
    }

    public function testGetSubscribedEventsDoesntCrash(){
        $this->subscriber->getSubscribedEvents();
    }

    private function createRequest($contentType, $body)
    {
        $request = new Request(array(), array(), array(), array(), array(), array(), $body);
        $request->headers->set('CONTENT_TYPE', $contentType);

        return $request;
    }

    private function createFilterControllerEventMock(Request $request, $method) {
        $controller = new MockController();

        $event = $this->getMockBuilder(' Symfony\Component\HttpKernel\Event\FilterControllerEvent')
            ->disableOriginalConstructor()
            ->setMethods(array('getController', 'getRequest'))
            ->getMock();

        $event->expects($this->any())
            ->method('getController')
            ->will($this->returnValue( array( $controller, $method ) ));

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue( $request ));


        return $event;
    }

    private function createGetResponseForControllerResultEvent( Request $request, $result ){
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent')
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getControllerResult'))
            ->getMock();

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $event->expects($this->any())
            ->method('getControllerResult')
            ->will($this->returnValue($result));

        return $event;
    }

    private function createGetResponseForExceptionEvent( \Exception $exception, Response $response = null ){
        $request = $this->createRequest("application/json", "");
        $request->headers->add(array("Accept" => "application/json"));

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->disableOriginalConstructor()
            ->setMethods(array('getException', 'getResponse', 'getRequest'))
            ->getMock();

        $event->expects($this->any())
            ->method('getException')
            ->will($this->returnValue( $exception ));

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue( $request ));

        $event->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue( $response ));

        return $event;
    }


    private function createGetResponseEventMock(Request $request) {
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
