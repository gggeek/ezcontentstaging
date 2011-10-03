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
    /// @todo implement some filter parameters, to eg. be able to fetch non-syncing items
    static function fetchSyncItems( $target_id=false, $offset=false, $limit=false )
    {
        return array( 'result' => eZContentStagingItem::fetchList( $target_id, true, $offset, $limit ) );
    }

    static function fetchSyncItemsCount( $target_id=false )
    {
        return array( 'result' => eZContentStagingItem::fetchListCount( $target_id ) );
    }

    static function fetchObjectSyncTargets( $object_id )
    {
         return array( 'result' => eZContentStagingItem::fetchByObject( $object_id ) );
    }

    static function fetchSynctarget( $target_id=false )
    {
        return array( 'result' => eZContentStagingTarget::fetch( $target_id ) );
    }
}

?>