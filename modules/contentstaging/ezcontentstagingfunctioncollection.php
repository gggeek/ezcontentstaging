<?php
/**
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZContentStagingFunctionCollection
{
    /// @todo implement some filter parameters, to eg. be able to fetch non-currently-syncing items
    static function fetchSyncEvents( $target_id=false, $offset=false, $limit=false )
    {
        return array( 'result' => eZContentStagingEvent::fetchList( $target_id, true, $offset, $limit ) );
    }

    static function fetchSyncEventsCount( $target_id=false )
    {
        return array( 'result' => eZContentStagingEvent::fetchListCount( $target_id ) );
    }

    /*static function fetchObjectSyncTargets( $object_id )
    {
         return array( 'result' => eZContentStagingEvent::fetchByObject( $object_id ) );
    }*/

    /// @todo implement some filter parameters, to eg. be able to fetch non-currently-syncing items
    static function fetchSyncEventsByNodeGroupedByTarget( $node_id, $object_id=false )
    {
        return array( 'result' => eZContentStagingEvent::fetchByNodeGroupedByTarget( $node_id, $object_id ) );
    }

    static function fetchSynctarget( $target_id=false )
    {
        return array( 'result' => eZContentStagingEvent::fetch( $target_id ) );
    }
}

?>