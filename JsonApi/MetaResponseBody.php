<?php
namespace Recognize\ApiBundle\JsonApi;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MetaResponseBody extends JsonResponse {

    public function __construct( $data = null, $status = 200, $headers = array() ){
        $headers['Content-Type'] = "application/vnd.api+json";
        parent::__construct( array( "meta" => $data ), $status, $headers);
    }
}