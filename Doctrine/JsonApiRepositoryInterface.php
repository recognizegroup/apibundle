<?php
namespace Recognize\ApiBundle\Doctrine;

use Symfony\Component\HttpFoundation\Request;

interface JsonApiRepositoryInterface {

    /**
     * Parse the request to execute a proper DQL expression
     *
     * @param Request $request
     * @param array $exposed_fields                     The exposed fields that the client can query
     * @param array $additional_criteria                Optional additional criteria to add to the query
     * @return mixed
     */
    public function jsonApiQuery( Request $request, $exposed_fields = array(), $additional_criteria = array() );

}