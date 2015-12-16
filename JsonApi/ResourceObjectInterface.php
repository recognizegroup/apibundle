<?php
namespace Recognize\ApiBundle\JsonApi;

interface ResourceObjectInterface {

    public function getId();

    /**
     * Get the resource type defined in the definitions/resources.json file
     *
     * @return mixed
     */
    public function getResourceType();

    /**
     * The attributes defined for this JSONAPI resource - Note: Relationships do not belong here
     *
     * @return array
     */
    public function getAttributes();

    /**
     * Get an key-value array where the value is an array of resourceObjects
     *
     * @return array
     */
    public function getRelationships();

}