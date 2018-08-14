<?php

namespace Recognize\ApiBundle\EventListener;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Psr\Log\LoggerInterface;
use Recognize\ApiBundle\Utils\JsonApi;
use Recognize\ApiBundle\Validator\JsonApiSchemaValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class JSONAnnotationSubscriber
 * @package Mapxact\ApiBundle\EventListener
 * @author Willem Slaghekke <w.slaghekke@recognize.nl>
 * @author Kevin te Raa <k.teraa@recognize.nl>
 */
class JSONAnnotationSubscriber implements EventSubscriberInterface {

    /** @var FileCacheReader */
    protected $reader;

    protected $verbose_environment = false;

    protected $validator;

    public function __construct(Reader $reader, LoggerInterface $logger, JsonApiSchemaValidator $jsonSchemaValidator, $environment) {
        $this->reader = $reader;
        $this->logger = $logger;
        $this->validator = $jsonSchemaValidator;

        if( $environment !== "prod"){
            $this->verbose_environment = true;
        }
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event) {
        if(!is_array($controller = $event->getController())) return; // Return when response is not an array
        list($object, $method) = $controller; // Get object and method

        $reflectionClass = new \ReflectionClass(ClassUtils::getClass($object));
        $reflectionMethod = $reflectionClass->getMethod($method);

        $annotations = $this->reader->getMethodAnnotations($reflectionMethod);
        $apiAnnotation = $this->findJSONAPIResponseAnnotation( $annotations );
        if(  $apiAnnotation !== false ){

            // Make sure the request uses the correct content type
            $this->validateJsonApiRequest( $event->getRequest() );
            $event->getRequest()->attributes->set('_json_api_response', true); // Use the strict JSON API spec

            if( $apiAnnotation->getSchema() === null ){
                throw new AnnotationException( "JSONAPIAnnotations require a schema!" );
            }

            $event->getRequest()->attributes->set("_json_schema", $apiAnnotation->getSchema() );
        } else if( $this->hasJSONResponseAnnotation( $annotations ) ) {
            $event->getRequest()->attributes->set('_json_response', true); // Use simple JSON transformation
        }
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event) {
        $isSimpleJson = $event->getRequest()->attributes->has('_json_response');

        // Skip over unannotated controller methods
        if (!$isSimpleJson &&
            !$event->getRequest()->attributes->has('_json_api_response')) return;

        // Do a simple transformation of the controller data
        // Put the controller data through json decoding to make sure JSON decoding interfaces work properly
        $result = json_decode( json_encode( $event->getControllerResult() ) );
        if($result === null || $result instanceof \stdClass == false) {
            $result = "";
        }/* else if( count($result) < 1 ){
            $result = "";
        }*/

        // Skip validation if we use the simple JSONResponse annotation
        if( $isSimpleJson ){
            if( $result == "" ){
                $event->setResponse( new Response( "", 200, array("Content-Type" => "application/json" ) ) );
            } else {
                $event->setResponse( new JsonResponse( $result , 200) );
            }

        } else {
            $this->validateJsonApiResponse( $event, $result );

            $jsonResponse = new JsonResponse( $result , 200, array("Content-Type" => JsonApi::CONTENT_TYPE ));
            $event->setResponse( $jsonResponse );
        }
    }

    /**
     * Validate the request's content type
     *
     * @param Request $event
     */
    protected function validateJsonApiRequest( Request $request ){
        $hasRequestData = strlen( $request->getContent() ) > 0;

        // Handles Firefox content-type BS
        if( $hasRequestData ){
            $positionOfContentType = strpos( $request->headers->get("Content-Type"), JsonApi::CONTENT_TYPE );
            if( $positionOfContentType === -1 || $positionOfContentType === false ) {
                throw new BadRequestHttpException("Content type " . $request->headers->get("Content-Type") . " not accepted - Use " . JsonApi::CONTENT_TYPE);
            }
        }
    }

    /**
     * Make sure the JSONAPI response validates
     *
     * @param GetResponseForControllerResultEvent $event
     * @param $resultdata
     */
    private function validateJsonApiResponse( GetResponseForControllerResultEvent $event, $resultdata ){
        $schema_uri = $event->getRequest()->attributes->get("_json_schema", null);

        if ( $this->validator->isValidJsonApiResponse( $resultdata, $schema_uri ) == false )
            $this->throwJsonError( $this->validator->getErrors(), $resultdata );
    }

    /**
     * Throw an HTTP exception in a new method to make sure the stacktrace is made properly
     *
     * @param $jsondata
     * @param $jsonerror
     */
    protected function throwJsonError( $jsonerror, $jsondata ){
        $this->logger->error("JSONSchema invalid \n " . json_encode( $jsondata  ) . " \nErrors\n" . json_encode( $jsonerror ) );
        throw new HttpException(500, "Incorrect JSON returned");
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event) {
        if(false === strpos($event->getRequest()->headers->get('Accept'), JsonApi::CONTENT_TYPE )
            && false === strpos($event->getRequest()->headers->get('Accept'), "application/json" )) return;

        $this->generateJsonApiErrorResponse( $event );
    }

    /**
     * @param GetResponseForExceptionEvent $event
     * @throws \Exception
     */
    protected function generateJsonApiErrorResponse( GetResponseForExceptionEvent $event ){
        $exception = $event->getException(); // Exception
        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatusCode($this->getStatusCode($event));

        $errorData = array(
            'errors' => array(
                array(
                    'status'   => $this->getStatusCode($event),
                    'title'    => $exception->getMessage(),
                )
            )
        );

        // Add stacktraces in development modes
        if( $this->verbose_environment ){
            $errorData['errors'][0]['meta'] = array(
                "stacktrace" => $exception->getTrace()
            );
        }

        $jsonResponse->setData( $errorData );
        $event->setResponse($jsonResponse);
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
     * @return int
     */
    protected function getStatusCode(GetResponseForExceptionEvent $event = null) {
        if($response = $event->getResponse()) { // When response is set
            return ($response->getStatusCode() != 0) ? $response->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        if($event->getException() instanceof HttpExceptionInterface) {
            /** @var HttpExceptionInterface $exception */
            $exception = $event->getException();
            return ($exception->getStatusCode() != 0) ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return ($event->getException()->getCode() != 0) ? $event->getException()->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * @param array $annotations
     * @return \Recognize\ApiBundle\Annotation\JSONResponse[]
     */
    protected function hasJSONResponseAnnotation(Array $annotations) {
        return count( array_filter($annotations, function($annotation) {
            return $annotation instanceof \Recognize\ApiBundle\Annotation\JSONResponse;
        }) ) > 0;
    }

    /**
     * @param array $annotations
     * @return \Recognize\ApiBundle\Annotation\JSONAPIResponse
     */
    protected function findJSONAPIResponseAnnotation(Array $annotations) {
        foreach( $annotations as $annotation ){
            if( $annotation instanceof \Recognize\ApiBundle\Annotation\JSONAPIResponse ){
                return $annotation;
            }
        }

        return false;
    }


    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            KernelEvents::CONTROLLER => array('onKernelController', -128),
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::VIEW => 'onKernelView'
        );
    }
}