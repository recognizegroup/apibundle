<?php
namespace Recognize\ApiBundle\Tests\Mocks;


use Recognize\ApiBundle\JsonApi\ResourceObjectInterface;

class MockResourceObject implements ResourceObjectInterface {

    protected $id;

    protected $type;

    protected $attributes;

    protected $relationships;

    public function __construct( $id, $type, $attributes, $relationships = array() ){
        $this->id = $id;
        $this->type = $type;
        $this->attributes = $attributes;
        $this->relationships = $relationships;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the resource type defined in the definitions/resources.json file
     *
     * @return mixed
     */
    public function getResourceType()
    {
        return $this->type;
    }

    /**
     * The attributes defined for this JSONAPI resource - Note: Relationships do not belong here
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get an key-value array where the value is an array of resourceObjects
     *
     * @return array
     */
    public function getRelationships()
    {
        return $this->relationships;
    }
}