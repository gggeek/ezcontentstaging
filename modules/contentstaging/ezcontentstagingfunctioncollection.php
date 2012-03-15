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
<<<<<<< HEAD
    static public function fetchSyncEvents( $target_id=false, $offset=false, $limit=false, $language=false, $status=null )
=======
    static function fetchSyncEvents( $target_id = false, $offset = false, $limit = false, $language = false )
>>>>>>> d3f2787... CS: fixed various space issues
    {
        return array( 'result' => eZContentStagingEvent::fetchList( $target_id, true, $offset, $limit, $language, $status ) );
    }

<<<<<<< HEAD
    static public function fetchSyncEventsCount( $target_id=false, $language=false, $status=null )
=======
    static function fetchSyncEventsCount( $target_id = false, $language = false )
>>>>>>> d3f2787... CS: fixed various space issues
    {
        return array( 'result' => eZContentStagingEvent::fetchListCount( $target_id, $language, $status ) );
    }

    /// @todo implement some filter parameters, to eg. be able to fetch non-currently-syncing items
<<<<<<< HEAD
    static public function fetchSyncEventsByNodeGroupedByTarget( $node_id, $object_id=false, $language=false )
=======
    static function fetchSyncEventsByNodeGroupedByTarget( $node_id, $object_id = false, $language = false )
>>>>>>> d3f2787... CS: fixed various space issues
    {
        return array( 'result' => eZContentStagingEvent::fetchByNodeGroupedByTarget( $node_id, $object_id, $language ) );
    }

<<<<<<< HEAD
    static public function fetchSynctarget( $target_id=false )
=======
    static function fetchSynctarget( $target_id = false )
>>>>>>> d3f2787... CS: fixed various space issues
    {
        return array( 'result' => eZContentStagingEvent::fetch( $target_id ) );
    }

<<<<<<< HEAD
    static public function fetchSyncEventsByObject( $target_id=false, $offset=false, $limit=false, $language=false )
=======
    static function fetchSyncEventsByObject( $target_id = false, $offset = false, $limit = false, $language = false )
>>>>>>> d3f2787... CS: fixed various space issues
    {
        return array( 'result' => eZContentStagingEvent::fetchListGroupedByObject( $target_id, true, $offset, $limit, $language ) );
    }

<<<<<<< HEAD
    /*static public function fetchObjectSyncTargets( $object_id )
    {
        return array( 'result' => eZContentStagingEvent::fetchByObject( $object_id ) );
    }*/

    static public function fetchFeedsByNodeId( $node_id=false )
=======
    static function fetchFeedsByNodeId( $node_id = false )
>>>>>>> d3f2787... CS: fixed various space issues
    {
        return array( 'result' => eZContentStagingTarget::fetchByNode( $node_id ) );
    }

}
