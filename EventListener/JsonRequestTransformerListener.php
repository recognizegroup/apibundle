<?php

namespace Recognize\ApiBundle\EventListener;

use Recognize\ApiBundle\Utils\JsonApi;
use Recognize\ApiBundle\Validator\JsonApiSchemaValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Transforms the body of a vnd.api+json request to POST parameters.
 */
class JsonRequestTransformerListener
{

    /** @var  JsonApiSchemaValidator */
    protected $validator;

    public function __construct( JsonApiSchemaValidator $jsonSchemaValidator ){
        $this->validator = $jsonSchemaValidator;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function __invoke(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (! $this->isJsonRequest($request) && ! $this->isJsonApiRequest( $request ) ) {
            return;
        }

        if (! $this->transformJsonBody($request)) {
            $content_type = $this->isJsonApiRequest( $request ) ? JsonApi::CONTENT_TYPE : "application/json";

            $event->setResponse( new JsonResponse(array(
                'errors' => array(
                array(
                    'status'   => "400",
                    'title'    => "Unable to parse the request body as valid JSON",
                )
            )), 400, array( "Content-Type" => $content_type ) ) );
        }

        $this->validateJsonApiSchema( $event );
    }

    /**
     * @param GetResponseEvent $event
     */
    private function validateJsonApiSchema( GetResponseEvent $event ){
        $request = $event->getRequest();
        $content = $request->getContent();

        if( empty( $content ) == false &&
            $this->isJsonApiRequest( $request ) &&
            $this->validator->isValidJsonApiRequest( $request ) == false ){

            $errors = array(
                array(
                    'status'   => "400",
                    'title'    => "The request body's JSON was given in an invalid schema",
                )
            );

            foreach( $this->validator->getErrors() as $error ){
                $errors[] = array( "status" => "400", "title" => $error['message'] );
            }

            $event->setResponse( new JsonResponse(array(
                'errors' => $errors ), 400, array( "Content-Type" => JsonApi::CONTENT_TYPE ) ) );
        }

    }

    private function isJsonApiRequest( Request $request ){
        return strpos($request->headers->get("Content-Type"), JsonApi::CONTENT_TYPE) !== false;
    }

    private function isJsonRequest(Request $request){
        return 'json' === $request->getContentType();
    }

    private function transformJsonBody(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        if ($data === null) {
            return true;
        }

        $request->request->replace($data);

        return true;
    }
}
