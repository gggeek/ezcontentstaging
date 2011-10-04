<?php
/**
 * Class used to hold definitions of staging targets
 * Every target is defined in term of
 * - a list of content trees to sync
 * - a class to be used for transport
 * - transport-specific settings
 *
 * So far hides access to ini file, as later we might want to convert this to
 * a db-based structure
 *
 * @version $Id$
 * @copyright 2011
 */

// Extremely quick and dirty "template object" from an ini settings group
class eZContentStagingTarget
{
    protected $_attrs = array();

    function __construct( $row )
    {
        $this->_attrs = self::CamelCase2camel_case( $row );
    }

    function attributes()
    {
        return array_keys( $this->_attrs );
    }

    function attribute( $attrname )
    {
        return $this->_attrs[$attrname];
    }

    /**
    * Returns list of target hosts defined in the system
    *
    * @return array
    */
    /*static function fetchIDList()
    {
        $ini = ezini( 'contentstaging.ini' );
        return $ini->variable( 'GeneralSettings', 'TargetList' );
    }*/

    static function fetchList()
    {
        $ini = eZINI::instance( 'contentstaging.ini' );
        foreach(  $ini->variable( 'GeneralSettings', 'TargetList' ) as $id )
        {
            $out[$id] = new eZContentStagingTarget( $ini->group( 'Target_' . $id ) );
        }
        return $out;
    }

    static function fetch( $id )
    {
        $ini = eZINI::instance( 'contentstaging.ini' );
        return new eZContentStagingTarget( $ini->group( 'Target_' . $id ) );
    }

    /**
    * Returns list of targets that should be notified of a given node
    */
    static function fetchByNode( eZContentObjectTreeNode $node )
    {
        $out = array();
        foreach( self::fetchList() as $id => $target );
        {
            if ( $target->includesNode( $node ) )
            {
                $out[$id] = $target;
            }
        }
        return $target;
    }

    /**
    * @return boolean
    */
    function includesNode( eZContentObjectTreeNode $node )
    {
        $nodepath = $node->attribute( 'path_string' );
        foreach( $this->_attrs['subtrees'] as $subtreeRoot )
        {
            if ( strpos( $nodepath, '/' . $subtreeRoot . '/' ) !== false )
            {
                return true;
            }
        }
        return false;
    }

    function includesNodeByPath( $nodepath )
    {
        foreach( $this->_attrs['subtrees'] as $subtreeRoot )
        {
            if ( strpos( $nodepath, '/' . $subtreeRoot . '/' ) !== false )
            {
                return true;
            }
        }
        return false;
    }

    /// CamelCase to camel_case conversion
    function CamelCase2camel_case( $array )
    {
        foreach( $array as $key => $val )
        {
            $name = strtolower( implode( '_', preg_split( '/([[:upper:]][[:lower:]]+)/', $key, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY ) ) );
            $out[$name] = $val;
        }
        return $out;
    }

}

?>