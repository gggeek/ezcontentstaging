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

class eZContentStagingTarget
{
    // Extremely quick and dirty "template object"
    protected $_attrs = array();

    function __construct( $row )
    {
        $_attrs = $row;
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
    static function fetchIDList()
    {
        $ini = ezini( 'contentstaging.ini' );
        return $ini->variable( 'GeneralSettings', 'TargetList' );
    }

    static function fetch( $id )
    {
        $ini = ezini( 'contentstaging.ini' );
        return new eZContentStagingTarget( $ini->group( 'Target_' . $id ) );
    }

}

?>