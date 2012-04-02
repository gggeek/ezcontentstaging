<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZContentStagingFunctionCollection
{
    /// @todo implement some filter parameters, to eg. be able to fetch non-currently-syncing items
    static public function fetchSyncEvents( $target_id = false, $offset = false, $limit = false, $language = false, $status = null )
    {
        return array( 'result' => eZContentStagingEvent::fetchList( $target_id, true, $offset, $limit, $language, $status ) );
    }

    static public function fetchSyncEventsCount( $target_id = false, $language = false, $status = null )
    {
        return array( 'result' => eZContentStagingEvent::fetchListCount( $target_id, $language, $status ) );
    }

    /// @todo implement some filter parameters, to eg. be able to fetch non-currently-syncing items
    static public function fetchSyncEventsByNodeGroupedByTarget( $node_id, $object_id = false, $language = false )
    {
        return array( 'result' => eZContentStagingEvent::fetchByNodeGroupedByTarget( $node_id, $object_id, $language ) );
    }

    static public function fetchSynctarget( $target_id = false )
    {
        return array( 'result' => eZContentStagingEvent::fetch( $target_id ) );
    }

    static public function fetchSyncEventsByObject( $target_id = false, $offset = false, $limit = false, $language = false )
    {
        return array( 'result' => eZContentStagingEvent::fetchListGroupedByObject( $target_id, true, $offset, $limit, $language ) );
    }

    /*static public function fetchObjectSyncTargets( $object_id )
    {
        return array( 'result' => eZContentStagingEvent::fetchByObject( $object_id ) );
    }*/

    static public function fetchFeedsByNodeId( $node_id = false )
    {
        return array( 'result' => eZContentStagingTarget::fetchByNode( $node_id ) );
    }

}
