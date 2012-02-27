<?php
/**
* Interface for classes implementing generation of remote ids
*
* @package ezcontentstaging
*
* @author
* @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

class eZContentStagingLocalAsRemoteIdGenerator implements eZContentStagingRemoteIdGenerator
{
    protected $target = null;

    function __construct( $target )
    {
        $this->target = $target;
    }

    /**
    * Uses local id on source as remote id on target server, with a prefix
    * @todo verify that remote id built is not longer than 32 chars
    */
    function buildRemoteId( $sourceId, $sourceRemoteId, $type='node' )
    {
        return "ezcs:" . $this->target . ':' . $sourceId;
    }
}

?>
