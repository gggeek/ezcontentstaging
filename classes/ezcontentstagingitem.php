<?php
/**
* The persistent class used to store information about which objects need to be
* synced to target servers - every content object that is modified gets a line in
* the eZContentStagingItem table for every existing target host.
* When the object is synced, the line is removed.
* If the object is modified again before its sync, the modified and to_sync values
* are aupdated, but no new line is created.
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*
* @todo review list of SYNC_* constants: should they match more closely event actions?
*/

class eZContentStagingItem extends eZPersistentObject
{

    const SYNC_PUBLICATION = 1;
    const SYNC_DELETION = 2;
    const SYNC_VISIBILITY = 4;
    const SYNC_NODES = 8;
    const SYNC_SECTION = 16;
    const SYNC_STATES = 32;
    const SYNC_SORTORDER = 64;

    const STATUS_TOSYNC = 0;
    const STATUS_SYNCING = 1;
    const STATUS_SUSPENDED = 2;

    static function definition()
    {
        return array( 'fields' => array( 'id' => array( 'name' => 'ID',
                                                        'datatype' => 'integer',
                                                        'required' => true ),
                                         'target_id' => array( 'name' => 'TargetID',
                                                        'datatype' => 'string',
                                                        'required' => true ),
                                         'object_id' => array( 'name' => 'ObjectID',
                                                               'datatype' => 'integer',
                                                               'required' => true,
                                                               'foreign_class' => 'eZContentObject',
                                                               'foreign_attribute' => 'id',
                                                               'multiplicity' => '1..*' ),
                                         // we store a custom modification date of object, as it includes metadata modifications
                                         'modified' => array( 'name' => 'Modified',
                                                              'datatype' => 'integer',
                                                              'required' => true ),
                                         // bitmap of things to sync
                                         'to_sync' => array( 'name' => 'ToSync',
                                                             'datatype' => 'integer',
                                                             'required' => true ),
                                         // used to avoid double syncing in parallel
                                         'status' => array( 'name' => 'Status',
                                                            'datatype' => 'integer',
                                                            'default' => 0,
                                                            'required' => true ),
                                         // we store extra data here, eg. description of deleted objects
                                         /// @tood check proper datatype for longtext cols
                                         'data' => array( 'name' => 'data',
                                                          'datatype' => 'text' ),
                                         'sync_begin_date' => array( 'name' => 'SyncBeginDate',
                                                                     'datatype' => 'integer',
                                                                     'required' => false,
                                                                     'default' => null ),
                                         // are these fields actually needed ?
                                         /*'synced' => array( 'name' => 'SyncDate',
                                                            'datatype' => 'integer',
                                                            'default' => null ),
                                         'sync-failures' => array( 'name' => 'SyncFailures',
                                                                   'datatype' => 'integer',
                                                                   'default' => 0,
                                                                   'required' => true )*/ ),
                      'keys' => array( 'target_id', 'object_id' ),
                      'increment_key' => 'id',
                      'function_attributes' => array( 'object' => 'getObject',
                                                      'target' => 'getTarget',
                                                      'can_sync' => 'canSync',
                                                      'events' => 'getEvents' ),
                      //'increment_key' => 'id',
                      'class_name' => 'eZContentStagingItem',
                      'sort' => array( 'id' => 'asc' ),
                      'name' => 'ezcontentstaging_item' );
    }

    /**
    * fetch a specific sync item
    * @return eZContentStagingItem or null
    */
    static function fetch( $target_id, $object_id, $asObject = true )
    {
        return self::fetchObject( self::definition(),
                                  null,
                                  array( 'target_id' => $target_id, 'object_id' => $object_id ),
                                  $asObject );
    }

    static function fetchByObject( $object_id, $asObject = true )
    {
        return self::fetchObjectList( self::definition(),
                                      null,
                                      array( 'object_id' => $object_id ),
                                      null,
                                      array(),
                                      $asObject );
    }

    /**
    * fetch all items that need to be synced to a given server (or all of them)
    * @return array
    */
    static function fetchList( $target_id=false, $asObject = true, $offset = false, $limit = false )
    {
        $conditions = array();
        if ( $target_id != '' )
        {
            $conditions = array( 'target_id' => $target_id );
        }
        $limits = array();
        if ( $offset !== false )
            $limits['offset'] = $offset;
        if ( $limit !== false )
            $limits['limit'] = $limit;
        return self::fetchObjectList( self::definition(),
                                      null,
                                      $conditions,
                                      null,
                                      $limits,
                                      $asObject );
    }

    /**
    * Returns count of items to sync
    * If no feed given, groups by object id
    */
    static function fetchListCount( $target_id=false )
    {
        if ( $target_id != '' )
        {
            return self::count( self::definition(), array( 'target_id' => $target_id) );
        }
        else
        {
            $customFields = array( array( 'operation' => 'COUNT( * )', 'name' => 'row_count' ) );
            $rows = self::fetchObjectList( self::definition(), array(), array(), array(), null, false, array( 'target_id' ), $customFields );
            return $rows[0]['row_count'];
        }
    }

    /**
    * Returns content object that this sync item refers to.
    * In case obj has been deleted, returns data that was stored at time of its
    * deletion (which is not a complete obj, but has some of its data: name, etc...)
    */
    function getObject()
    {
        $return = eZContentObject::fetch( $this->ObjectID );
        if ( !$return )
        {
            // obj has been deleted, and we should have soem obj data stored within the item
            $data = json_decode( $this->data );
            if ( isset( $data['object'] ) )
            {
                $return = $data['object'];
            }
            else
            {
                 eZDebug::writeError( "Object " . $this->ObjectID . " gone amiss for sync item. Target" . $this->TargetID, __METHOD__ );
            }
        }
        return $return;
    }

    function getTarget()
    {
        return eZContentStagingTarget::fetch( $this->TargetID );
    }

    function getEvents()
    {
        return eZContentStagingItemEvent::fetchByItem( $this->TargetID, $this->ObjectID );
        /*if ( $this->_events !== null )
        {
            return $this->_events;
        }
        $data = json_decode( $this->data );
        if ( !is_array( @$data['events'] ) )
        {
            eZDebug::writeError( "Events gone amiss for sync item " . $this->ID, __METHOD__ );
            $this->_events = array();
            return null;
        }
        $this->_events = $data['events'];
        return $data['events'];*/
    }

    /**
    * Adds an event to an item. If item is not exsisting, creates it
    * @todo should we lock item row for update before checking max event id?
    */
    static function addEvent( $target_id, $object_id, $action, $data )
    {
        $time = time();
        $db = eZDB::instance();

        // look up: does item exist? if not, create it, else update it
        $item = self::fetch( $target_id, $object_id );
        if ( is_object( $item ) )
        {
            $item->Modified = $time;
            $item->ToSync = self::tosyncBitmask( $action, $item->ToSync );
            /// @todo use ezpo syntax here
            /// @todo test index usage for this select
            $evtId = $db->arrayquery( "select max( id )+1 as id from ezcontentstaging_item_event where target_id = '" . $db->escapeString( $target_id ) ."' and object_id = " . $db->escapeString( $object_id ) );
            $evtId = $evtId[0]['id'];
        }
        else
        {
            $item = new eZContentStagingItem( array(
                'target_id' => $target_id,
                'object_id' => $object_id,
                'modified' => $time,
                'to_sync' => self::tosyncBitmask( $action )
            ) );
            $evtId = 1;
        }

        // begin transaction as late as possible
        $db->begin();

        $item->store();

        // add events
        $event = new eZContentStagingItemEvent( array(
            'target_id' => $target_id,
            'object_id' => $object_id,
            'id' => $evtId,
            'created' => $time,
            'type' => $action,
            'data' => json_encode( $data )
            ) );
        $event->store();

        $db->commit();
    }

    /**
    * @param array $events
    */
    /*function setEvents( array $events )
    {
        /// @todo

        /*$this->_events = $event;
        $this->setHasDirtyData( true );* /
    }*/

    /**
    *
    * @return integer 0 on sucess
    * @todo after 3 consecutive errors, suspend sync?
    */
    function syncItem()
    {

        // use transport class to sync the current changes
        $target = eZContentStagingTarget::fetch( $this->TargetID );
        $class = $target->attribute( 'transport_class' );
        if ( !class_exists( $class ) )
        {
            eZDebug::writeError( "Failed syncing item to target " . $this->TargetID . ", class $class not found", __METHOD__ );
            return -10;
        }

        if ( $this->Status != self::STATUS_TOSYNC )
        {
            return $this->Status * -1;
        }

        // set status to 'pending'
        $this->Status = self::STATUS_SYNCING;
        $this->SyncBeginDate = time();
        $this->store();

        $transport = new $class( $target );

        /// @todo add logging (ezdebug.ini based)
        $result = $transport->sync( $this );
        if ( $result != 0 )
        {
            eZDebug::writeError( "Failed syncing item " . $this->TargetID . "/". $this->ObjectID . ", transport error code: $result", __METHOD__ );
            $this->Status = self::STATUS_TOSYNC;
            $this->SyncBeginDate = null;
            $this->store();
            return $result;
        }

        /// @todo : check if by chance someone else updated the node while we where
        // syncing (modified, to_sync), if so: sync again

        $db = eZDB::instance();
        $db->begin();
        eZContentStagingItemEvent::removeByItem( $this->TargetID, $this->ObjectID );
        $this->remove();
        $db->commit();
        return 0;
    }

    /// @todo to be augmented (or replaced) with current user perms checking
    function canSync( )
    {
        return $this->Status == self::STATUS_TOSYNC;
    }

    /// @todo finish function...
    static function tosyncBitmask( $action, $currentbitmask=0 )
    {
        switch( $action )
        {
            case eZContentStagingItemEvent::ACTION_ADDLOCATION:
            case eZContentStagingItemEvent::ACTION_REMOVELOCATION:
                return $currentbitmask | self::SYNC_NODES;
        }
    }

}

?>
