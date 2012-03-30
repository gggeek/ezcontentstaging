<?php
/**
* Interface for classes implementing generation of remote ids
*
* @package ezcontentstaging
*
* @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

class eZContentStagingSameRemoteIdGenerator implements eZContentStagingRemoteIdGenerator
{
    public function __construct( $target )
    {
    }

    /**
     * Uses the same remote id on source and on target server
     */
<<<<<<< HEAD
    function buildRemoteId( $sourceId, $sourceRemoteId, $type='node' )
=======
    public function buildRemoteId( $sourceId, $sourceRemoteId, $type = 'node' )
>>>>>>> 4bc5afa... CS: fixed scope in method declaration
    {
        return $sourceRemoteId;
    }
}
