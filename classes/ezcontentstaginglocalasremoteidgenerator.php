<?php
/**
* Interface for classes implementing generation of remote ids
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

class eZContentStagingLocalAsRemoteIdGenerator implements eZContentStagingRemoteIdGenerator
{
    /**
    * Uses local id on source as remote id on target server, with a prefix
    * @todo verify that remote id built is not longer than 32 chars
    */
    function buildRemoteId( $sourceId, $sourceRemoteId, $target, $type='node' )
    {
        return "ezcs:" . $target . ':' . $sourceId;
    }
}

?>
