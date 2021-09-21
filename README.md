Recognize ApiBundle
========================

This bundle is a collection of utility classes and annotations that help the creation of API's using JSON.

Features include:
* Transforming JSON requests into a working ParameterBag
* JsonSchema validation for JSONAPI
* Enforces JSONAPI when using the JSONAPIResponse annotation
* Allows filter, sort and include queries with the JsonApiRepositoryTrait

Supported JSONAPI query parameters include:
* filter[field][search]=test     - Equality checking if the field contains the string test
* filter[field][eq]=test         - Equality checking if the field is equal to test
* filter[field][in]=test,nottest - Check if the field is either test or nottest
* filter[field][range]=0,100     - Range checking if the field is between 0 and 100
* sort=field1,-field2            - Sorting field1 ascending and field2 descending
* includes=field3,field3.field4  - Query the database for the field3 association and the field3.field4 association

Installation
-----------

Add the bundle to your composer.json

```json
# composer.json
{
	"repositories": [
		{
			"type": "git",
			"url":  "https://github.com/recognizegroup/apibundle.git"
		}
	],
	 "require": {
		"recognize/apibundle": "dev-master",
	}
}
```

Run composer install

```sh
php ./composer.phar install
```

Enable the bundle in the kernel

```php
	<?php
	// app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Recognize\ApiBundle\RecognizeApiBundle(),
        );
    }
```

Add the listeners to your project.
When the listeners are added, JSON request bodies are automatically transformed and added to the request.


```yml
// app/config

imports
    - { resource: @RecognizeApiBundle/Resources/config/listeners.yml }
```

And the path where the jsonschema objects are found.

```yml
// app/config

recognize_api:
    schema_directory: '%kernel_rootdir%./doc/schemas'
```

Annotations
--------------
 
To use the transformation of controller data to json, either add the non-validating JSONResponse Annotation
or the strict JSONAPIResponse Annotation to your method documentation.

```php
class Controller {

	/**
	 * JSONAPIResponse( "testresponse.json" )
	 */
	public function testAction(){
	
	}
	
}
```

The JSONAPIResponse validates the incoming data as well as the outgoing data to the JSON API V1.0 specifications.
You must add a valid schema filename to the annotation, so that the outgoing data can be tested for correctness.

JSON Schemas
============

In your schemas, you can use the definitions defined in the jsonapi.json schema file ( Resources/schemas/jsonapi.json )
to make it easier to set up your schemas. For example:

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "Test write request",
  "description": "The default schema for a create request",
  "type": "object",
  "allOf": [
    {
      "$ref": "#/jsonapi/resource_response"
    },
    {
      "type": "object",
      "properties": {
        "data": {
          "type": "object",
          "properties": {
            "type": {
              "type": "string",
              "enum": [ "test" ]
            }
          }
        }
      }
    }
  ]
}
```

This schema will validate against both the JSONAPI v1.0 specification for a single resource response
and if the type parameter of the data object is "test".

Custom JSON Schema definitions
-------------

You can also define custom definitions by adding them to the definitions configuration.
An example is shown below.

```json
// definitions.json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "definitions": {
    "name": { "type": "string", "pattern": "/^k" }
  }
}
```

```yml
// app/config/
recognize_api:
    schema_directory: '%kernel_rootdir%./doc/schemas'
    definitions:
        - { path: "definitions.json", "property": "definitions" }     
```

In this example, we can use the reference '#/definitions/name' in our json schemas to use the name type defined in the definitions.json file like the schema shown below.

```json
//example_schema.json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "Test write request",
  "description": "The default schema for a create request",
  "type": "object",
  "allOf": [
    {
      "$ref": "#/jsonapi/resource_response"
    },
    {
      "type": "object",
      "properties": {
        "data": {
          "type": "object",
          "properties": {
            "attributes": {
              "type": "object",
              "properties": {
                "name": { "$ref": "#/definitions/name" }
              }
            }
          }
        }
      }
    }
  ]
}
```

Clientside queries using sort, filter and include query parameters
------------------------

To allow the client to query using JSONAPI standards, you can add the JsonApiRepositoryTrait to your EntityRepository.

```php
class ExampleRepository extends EntityRepository implements JsonApiRepositoryInterface
{
    use JsonApiRepositoryTrait;
    
}
```

This exposes a jsonApiQuery method that returns a collection of items the client has requested.
You must always set the fields the client is allowed to query for security reasons. 
If fields aren't exposed, queries based on them will not be executed.

```php
class ExampleController extends Controller {

  /** @var ExampleRepository $exampleRepo */
  protected $exampleRepo;

  public function indexAction( Request $request ){
      return $exampleRepo->jsonApiQuery( $request, array( "name", "date" ) );
  }

}
```

This example allows the client to sort or query the name and date fields.

As of right now, the include query does query the database for the relationships, but they aren't added to the response yet.

