<?php
/**
 * Class used to hold definitions of staging target servers.
 * So far hides access to ini file, as later we might want to convert this to
 * a db-based structure
 *
 * @version $Id$
 * @copyright 2011
 */

class eZContentStagingTarget
{
    /**
    * Returns list of target hosts defined in the system
    *
    * @return array
    */
    static function fetchIDList()
    {
        $ini = ezini( 'contentstaging.ini' );
        return $ini->value( 'GeneralSettings', 'TargetList' );
    }
}

?>