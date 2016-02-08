<?php
namespace Recognize\ApiBundle\Utils;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class JsonApiQueryBuilder {

    /**
     * @param EntityManagerInterface $em
     * @param $entity
     */
    public function __construct( EntityManagerInterface $em, $entity ){
        $this->classname = is_string( $entity ) ? $entity : get_class( $entity );
        $this->em = $em;
    }

    /**
     * Use the container object received from the parameter bag parsing to generate a DQL query builder
     * By default selects the entity object
     *
     * @param $container
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function generateDoctrineQueryBuilder( $container, $allowed_query_fields = array() ){
        $qb = $this->em->createQueryBuilder();
        $qb->select("entity");
        $qb->from($this->classname, "entity");

        $join_parts = array();
        $where_queries = array();
        foreach( $container as $type => $queries ){
            foreach( $queries as $entitykey => $value ){

                $field = $entitykey;
                if( $type == "joins" && empty( $value ) == false ){
                    $field = $value[ 1 ];
                    if (strpos( $field, "entity.") === 0) {
                        $field = substr($field, 7);
                    }
                }

                if( in_array( $field, $allowed_query_fields ) && $this->fieldExists( $field ) ){
                    // Detect the right joins and values for this search key
                    $detectedvalues = $this->detectKeyAndJoins( $entitykey, $type );
                    $querykey = $detectedvalues['key'];
                    $parameter_key = $detectedvalues['parameter_key'];

                    if( $detectedvalues['joins'] > 0 ){
                        foreach( $detectedvalues['joins'] as $alias => $join ){
                            $join_parts[ $alias ] = $join;
                        }
                    }

                    switch( $type ){
                        case "eq":
                            if( empty( $value ) == false ){
                                $where_queries[] = $querykey . ' = :' . $parameter_key;
                                $qb->setParameter($parameter_key, $value );
                            }
                            break;
                        case "search":
                            if( empty( $value ) == false ){
                                $where_queries[] = $querykey . ' LIKE :' . $parameter_key;
                                $qb->setParameter($parameter_key, '%' . $value . '%');
                            }
                            break;
                        case "in":
                            if( empty( $value ) == false ){
                                $where_queries[] = $querykey . ' IN(:' . $parameter_key . ")";
                                $qb->setParameter($parameter_key, $value );
                            }
                            break;
                        case "range":
                            if( empty( $value ) == false && count( $value ) == 2 ){
                                $where_queries[] = $querykey . ' >= :' . $parameter_key . "_min AND "
                                    . $querykey . " <= :" . $parameter_key . "_max";
                                $qb->setParameter($parameter_key . "_min" , $value[0] );
                                $qb->setParameter($parameter_key . "_max" , $value[1] );
                            }

                            break;
                        case "joins":
                            if( empty( $value ) == false ){
                                $qb->addSelect( $value[ 0 ] );

                                // Add additional joins if they are required
                                $joins = $this->getDynamicJoins( $value[ 1 ] );
                                foreach( $joins as $alias => $relationship ){
                                    $join_parts[ $alias ] = $relationship;
                                }
                            }

                            break;
                        case "sort":
                            if(in_array(strtoupper($value), array('ASC','DESC'))) {
                                $qb->addOrderBy($querykey, $value);
                            }

                            break;
                    }
                }
            }
        }

        if( count( $where_queries ) > 0 ){
            $qb->where( join(" AND ", $where_queries ) );
        }

        /**
         * Add the automatic joins
         */
        foreach( $join_parts as $alias => $relationship ){
            $qb->leftJoin($relationship, $alias);
        }

        return $qb;
    }

    /**
     * Use the double underscores in the string to create automatic joins
     * And return the right key and parameter key to use in the DQL query
     *
     * For example: name will need entity.name in a DQL query,
     * While order.name will need a left join with the entity.order, and the DQL query will need order.name
     * in it
     *
     * @param $entitykey
     * @param $queryoption
     * @return
     */
    protected function detectKeyAndJoins( $entitykey, $queryoption ){
        $elements = explode(".", $entitykey);

        $keys = array(
            "joins" => array(),
            "key" => "entity." . $elements[0],
            "parameter_key" => $queryoption . "_" . $this->snakeCase( $entitykey ) );

        // Generate the join relations and aliasses that doctrine automatically understands
        if( count( $elements ) > 1 ){
            $joins = array();
            $alias = $elements[ 0 ];
            for( $i = 0, $lastjoinelement = count( $elements ) - 1; $i < $lastjoinelement; $i++ ){
                if( $i == 0 ){
                    $relation = "entity." . $elements[ $i ];
                } else {
                    $relation = $elements[ $i - 1 ] . "." . $elements[ $i ];
                }

                if( $i > 0 ){
                    $alias .= "_" . $elements[ $i ];
                }

                $keys[ "key" ] = $alias . "." . $elements[ $i + 1 ];
                $joins[ $alias ] = $relation;
            }

            $keys['joins'] = $joins;
        }

        return $keys;
    }

    /**
     * Get automated joins
     *
     * @param $entitykey
     */
    protected function getDynamicJoins( $entitykey ){
        $elements = explode(".", $entitykey);

        // Generate the join relations and aliasses that doctrine automatically understands
        $joins = array();
        if( count( $elements ) > 1 ){
            $alias = $elements[ 1 ];
            $relation = "entity";
            for( $i = 0, $lastjoinelement = count( $elements ); $i < $lastjoinelement; $i++ ){

                if( $i > 0 ){
                    $relation .= "." . $elements[ $i ];
                }

                if( $i > 1 ) {
                    $alias .= "_" . $elements[$i];
                }

                $joins[ $alias ] = $relation;
            }
        }

        return $joins;
    }

    /**
     * Turn any string into snake case
     *
     * @param $string
     */
    protected function snakeCase( $string ){
        return strtolower( str_replace( ".", "_", $string) );
    }

    /**
     * Check if the field actually exists so we don't expose database errors to the client
     *
     * @param $fieldname
     */
    protected function fieldExists( $fieldname ){
        $metadata = $this->em->getClassMetadata( $this->classname );

        if( strpos( $fieldname, "." ) !== false ) {
            if (strpos( $fieldname, "entity.") === 0) {
                $fieldname = substr($fieldname, 7);
            }

            $fields = explode(".", $fieldname);
            for ($i = 0; $i < count($fields) - 1; $i++) {
                $subclass = $metadata->getAssociationTargetClass($fields[$i]);
                $metadata = $this->em->getClassMetadata($subclass);
            }

            $association_fieldname = $fields[count($fields) - 1];

            return $metadata->hasAssociation( $association_fieldname )
                || $metadata->hasField( $association_fieldname );

        } else {
            return $metadata->hasField( $fieldname ) || $metadata->hasAssociation( $fieldname );
        }
    }
}