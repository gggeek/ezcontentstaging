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
    static public function fetchSyncEvents( $targetId = false, $offset = false, $limit = false, $language = false, $status = null )
    {
        return array( 'result' => eZContentStagingEvent::fetchList( $targetId, true, $offset, $limit, $language, $status ) );
    }

    static public function fetchSyncEventsCount( $targetId = false, $language = false, $status = null )
    {
        return array( 'result' => eZContentStagingEvent::fetchListCount( $targetId, $language, $status ) );
    }

    /// @todo implement some filter parameters, to eg. be able to fetch non-currently-syncing items
    static public function fetchSyncEventsByNodeGroupedByTarget( $nodeId, $objectId = false, $language = false )
    {
        return array( 'result' => eZContentStagingEvent::fetchByNodeGroupedByTarget( $nodeId, $objectId, $language ) );
    }

    static public function fetchSynctarget( $targetId = false )
    {
        return array( 'result' => eZContentStagingEvent::fetch( $targetId ) );
    }

    static public function fetchSyncEventsByObject( $targetId = false, $offset = false, $limit = false, $language = false )
    {
        return array( 'result' => eZContentStagingEvent::fetchListGroupedByObject( $targetId, true, $offset, $limit, $language ) );
    }

    /*static public function fetchObjectSyncTargets( $objectId )
    {
        return array( 'result' => eZContentStagingEvent::fetchByObject( $objectId ) );
    }*/

    static public function fetchFeedsByNodeId( $nodeId = false )
    {
        return array( 'result' => eZContentStagingTarget::fetchByNode( $nodeId ) );
    }
}
