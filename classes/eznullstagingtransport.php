<?php
/**
* Dummy class used to sync content to remote servers:
* always returns OK
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2

*/

class eZNullStagingTransport implements eZContentStagingTransport
{

    function __construct( eZContentStagingTarget $target )
    {

    }

    function sync( array $events )
    {
        return 0;
    }
}

?>
