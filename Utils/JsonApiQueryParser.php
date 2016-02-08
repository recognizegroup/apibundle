<?php
namespace Recognize\ApiBundle\Utils;

use Symfony\Component\HttpFoundation\ParameterBag;

class JsonApiQueryParser {

    /**
     * Collects the query elements from the parameter bag
     * For example: filter[name] = 'Test' will generate the following container
     * [ "exact": [ "name": "Test" ] ]
     *
     * @param ParameterBag $bag
     */
    public static function parseParameterBag( ParameterBag $bag ){
        $parameters = $bag->all();

        // Generate a container with all the query options
        $container = array();
        foreach( $parameters as $key => $value ) {
            if( strpos( $key, "filter" ) === 0 ){
                $filtercomponents = self::getFilterComponents( $value );
                foreach( $filtercomponents as $type => $queries ){
                    $container[ $type ] = $queries;
                }

            } else if( $key == "sort" ){
                $sort_values = explode( ",", $value );

                $sorts = array();
                foreach( $sort_values as $sort_value ){
                    $sort_direction = "DESC";
                    if( strpos($sort_value, "-" ) === 0 ){
                        $sort_value = substr($sort_value, 1 );
                        $sort_direction = "ASC";
                    }

                    $sorts[ $sort_value ] = $sort_direction;
                }

                $container['sort'] = $sorts;
            } else if( $key == "include" ){
                $joinfields = explode( ",", $value );

                $joins = array();
                foreach( $joinfields as $joinfield ){
                    $joins[] = array( str_replace(".", "_", $joinfield), "entity." . $joinfield );
                }

                $container['joins'] = $joins;
            }
        }

        return $container;
    }

    /**
     * Retrieve filter components from the filter array
     *
     * @param $filter
     */
    protected static function getFilterComponents( $filter ){
        $filtercomponents = array();

        foreach( $filter as $field => $values ){
            foreach( $values as $type => $value ){
                switch( $type ){
                    case "search":
                        if( array_key_exists( "search", $filtercomponents ) == false ){
                            $filtercomponents[ "search" ] = array();
                        }

                        $filtercomponents[ "search" ][ $field ] = $value;

                        break;
                    case "eq":
                        if( array_key_exists( "eq", $filtercomponents ) == false ){
                            $filtercomponents[ "eq" ] = array();
                        }

                        $filtercomponents[ "eq" ][ $field ] = $value;
                        break;
                    case "range":
                    case "in":
                        if( array_key_exists( $type, $filtercomponents ) == false ){
                            $filtercomponents[ $type ] = array();
                        }

                        $filtercomponents[ $type ][ $field ] = explode( ",", $value );
                        break;
                }
            }
        }

        return $filtercomponents;
    }

}