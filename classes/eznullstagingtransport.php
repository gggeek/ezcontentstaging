<?php
/**
* Dummy class used to sync content to remote servers:
* always returns OK
*
* @package ezcontentstaging
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

    function syncEvents( array $events )
    {
        return 0;
    }

    function checkNode( eZContentObjectTreeNode $node )
    {
        return 0;
    }

    function checkObject( eZContentObject $object )
    {
        return 0;
    }
}

?>
