<?php
/**
 * Interface for classes implementing generation of remote ids.
 * The idea is that for every node and object in the local db, the remote_id
 * field is used to map to the same node/object in the remote db.
 * But in some cases the two remote ids will be identical, while in some other
 * cases we might have more complex mappings.
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

interface eZContentStagingRemoteIdGenerator
{
    public function __construct( $target );

    /**
     * @return string
     * @todo This interface is crappy. Let's receive an object instead, and get the
     *       remote_id from it...
     */
    public function buildRemoteId( $sourceId, $sourceRemoteId, $type = 'node' );
}