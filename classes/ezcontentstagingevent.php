<?php
/**
* The persistent class used to store information about which objects/nodes need to be
* synced to target servers - every content-modifying action gets a line in
* the eZContentStagingEvent table for every existing target host.
* For actions affecting a single node, one row is created in the ezcontentstaging_event_node table
* (eg: hide/show)
* For actions affecting the whole object (eg: edit), many rows are added in the
* ezcontentstaging_event_node table.
* When the object is synced, the lines are removed.
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

class eZContentStagingEvent extends eZPersistentObject
{
    /// @todo !important reorder in same order as on fs / ezwstransport
    const ACTION_ADDLOCATION = 1;
    const ACTION_REMOVELOCATION = 2;
    const ACTION_UPDATESECTION = 4;
    const ACTION_HIDEUNHIDE = 8;
    const ACTION_SORT = 16;
    const ACTION_UPDATEPRIORITY = 32;
    const ACTION_DELETE = 64;
    const ACTION_REMOVETRANSLATION = 128;
    const ACTION_UPDATEALWAYSAVAILABLE = 256;
    const ACTION_UPDATEMAINASSIGNMENT = 512;
    const ACTION_UPDATEINITIALLANGUAGE = 1024;
    const ACTION_MOVE = 2048;
    const ACTION_PUBLISH = 4096;

    const STATUS_TOSYNC = 0;
    const STATUS_SYNCING = 1;
    const STATUS_SUSPENDED = 2;

    // error ranges:
    // event status ko: -1 to -9
    // event class errors: -10 to -99
    // transport class errors: -100 to -1000

    /// config error: php class for transport not found
    const ERROR_NOTRANSPORTCLASS = -10;
    /// transport class threw an exception
    const ERROR_TRANSPORTEXCEPTION = -11;
    // an error that should never be returned
    const ERROR_BADPHPCODING = -99;

    const ERROR_EVENTTYPEUNKNOWNTOTRANSPORT = -101;

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
                                         // this is only stored for some events. NULL == affects all languages
                                         'language_mask' => array( 'name' => 'LanguageMask',
                                                                   'datatype' => 'integer',
                                                                   'required' => false ),
                                         // type of event (what to sync)
                                         'to_sync' => array( 'name' => 'ToSync',
                                                             'datatype' => 'integer',
                                                             'required' => true ),
                                         // we store a custom modification date of object, as it includes metadata modifications
                                         /// @todo rename to 'created' ?
                                         'modified' => array( 'name' => 'Modified',
                                                              'datatype' => 'integer',
                                                              'required' => true ),
                                         // we store extra data here, eg. description of deleted objects
                                         'data_text' => array( 'name' => 'DataText',
                                                               'datatype' => 'text' ),

                                         // used to avoid double syncing in parallel
                                         'status' => array( 'name' => 'Status',
                                                            'datatype' => 'integer',
                                                            'default' => 0,
                                                            'required' => true ),
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
                      'keys' => array( 'id' ),
                      'increment_key' => 'id',
                      'function_attributes' => array( 'object' => 'getObject',
                                                      'target' => 'getTarget',
                                                      //'can_sync' => 'canSync',
                                                      'nodes' => 'getNodes',
                                                      'data' => 'getData' ),
                      'class_name' => 'eZContentStagingEvent',
                      'sort' => array( 'id' => 'asc' ),
                      'grouping' => array(), // only there to prevent a php warning by ezpo
                      'name' => 'ezcontentstaging_event' );
    }

    // ***  function attributes ***

    /**
     * Returns content object that this sync event refers to.
     * In case obj has been deleted, returns data that was stored at time of its
     * deletion (which is not a complete obj, but has some of its data: name, etc...)
     * @return eZContentObject
     */
    function getObject()
    {
        $return = eZContentObject::fetch( $this->ObjectID );
        if ( !$return )
        {
            // obj has been deleted, and we should have some obj data stored within the event
            $data = json_decode( $this->data );
            if ( isset( $data['object'] ) )
            {
                $return = $data['object'];
            }
            else
            {
                eZDebug::writeError( "Object " . $this->ObjectID . " gone amiss for sync event. Target" . $this->TargetID, __METHOD__ );
            }
        }
        return $return;
    }

    /// @return eZContentStagingTarget
    function getTarget()
    {
        /// @todo log error if target gone amiss
        return eZContentStagingTarget::fetch( $this->TargetID );
    }

    /// @return array
    function getData()
    {
        return json_decode( $this->DataText, true );
    }

    function getNodes()
    {
        $db = eZDB::instance();
        $nodeids = $db->arrayquery( 'SELECT node_id FROM ezcontentstaging_event_node WHERE ezcontentstaging_event_node.event_id = ' . $this->ID, array( 'column' => 'node_id' ) );
        /// @todo log error if count oif nodes found is lesser than stored node ids ?
        return self::fetchObjectList( eZContentObjectTreeNode::definition(),
                                      null,
                                      array( 'node_id' => array( $nodeids ) ),
                                      null,
                                      null );
    }

    // *** fetches ***

    /**
    * fetch a specific sync item
    * @return eZContentStagingEvent or null
    */
    static function fetch( $id, $asObject = true )
    {
        return self::fetchObject( self::definition(),
                                  null,
                                  array( 'id' => $id ),
                                  $asObject );
    }

    /**
     * Fetch all pending ecents for a given object, optionally filtered by feed.
     * 2nd param is there for optimal resource usage
     */
    static function fetchByObject( $object_id, $target_id = null, $asObject = true )
    {
        $conds = array( 'object_id' => $objectId );
        if ( $target_id != null )
        {
            $conds['target_id'] = $target_id;
        }
        return self::fetchObjectList( self::definition(),
                                      null,
                                      $conds,
                                      null,
                                      null,
                                      $asObject );
    }

    /**
    * Fetch all pending ecents for a given node, optionally filtered by feed.
    * 2nd param is there for optimal resource usage
    */
    static function fetchByNode( $nodeId, $objectId=null, $target_id = null, $asObject = true )
    {
        if ( $objectId == null )
        {
            $node = eZContentObjectTreeNode::fetch( $nodeId );
            if ( !$node )
            {
                eZDebug::writeWarning( "Node " . $node_id . " does not exist", __METHOD__ );
                return null;
            }
            $objectId = $node->attribute( 'contentobject_id' );
        }
        $conds = array( 'object_id' => $objectId );
        if ( $target_id != null )
        {
            $conds['target_id'] = $target_id;
        }
        return self::fetchObjectList( self::definition(),
                                      null,
                                      $conds,
                                      null,
                                      null,
                                      $asObject,
                                      /// @todo oracle support: all fields in select list should be in group by
                                      array( 'id' ),
                                      null,
                                      array( 'ezcontentstaging_event_node' ),
                                      ' AND ezcontentstaging_event_node.node_id = ' . (int)$nodeId . ' AND ezcontentstaging_event_node.event_id = id' );
    }

    static function fetchByNodeGroupedByTarget( $nodeId, $objectId=null )
    {
        $targets = array();
        $events = self::fetchByNode( $nodeId, $objectId=null );
        foreach( $events as $event )
        {
            $targets[$event->TargetID][] = $event;
        }
        return $targets;
    }
    /**
    * Fetch all items that need to be synced to a given server (or all of them)
    * @return array of
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
    * Returns count of items to sync to a given server
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

    // *** other actions ***

    /**
    * Adds an event to the queue.
    *
    * @todo add intelligent deduplication, eg: if there is an hide event then a show one,
    *       do not add show but remove hide, etc...
    */
    static function addEvent( $targetId, $objectId, $action, $data, $nodeIds=array(), $langMask=null )
    {
        if ( count( $nodeIds ) )
        {
            $db = eZDB::instance();
            $db->begin();
        }
        $event = new eZContentStagingEvent( array(
            'target_id' => $targetId,
            'object_id' => $objectId,
            'modified' => time(),
            'to_sync' => $action,
            'data_text' => json_encode( $data ),
            'language_mask' => $langMask
            ) );
        $event->store();
        $id = $event->ID;
        if ( count( $nodeIds ) )
        {
            foreach( $nodeIds as $nodeId )
            {
                $db->query( "insert into ezcontentstaging_event_node( event_id, node_id ) values ( $id, $nodeId )" );
            }
            $db->commit();
        }
    }

    /**
    * Syncs events to their respsective targets, then deletes them.
    * If events are already in syncing status, they are skipped.
    * If syncing succeeds, they are deleted, otherwise not
    * @return array of integer associative array, with event id as key. Values: 0 on sucess
    * @todo after 3 consecutive errors, suspend sync?
    * @todo figure out if we can be faster by batching events in calls to $transport::syncEvents
    *       while still maintaining correct ordering
    */
    static function syncEvents( array $events )
    {
        $results = array();

        // optimize usage of transport objects: build only one per target
        $transports = array();
        foreach( $events as $id => $event )
        {
            $target = eZContentStagingTarget::fetch( $event->TargetID );
            $class = $target->attribute( 'transport_class' );
            if ( !isset( $transports[$event->TargetID] ) )
            {
                if ( !class_exists( $class ) )
                {
                    eZDebug::writeError( "Can not sync event " . $event->ID . " to target " . $event->TargetID . ", class $class not found", __METHOD__ );
                    $results[$event->ID] = self::ERROR_NOTRANSPORTCLASS;
                    unset( $events[$id] );
                    continue;
                }
                $transports[$event->TargetID] = new $class( $target );
            }
            $results[$event->ID] = self::ERROR_BADPHPCODING; // this has to updated before we return
        }

        // coalescing events allows to send fewer of them
        self::coalesceEvents( $events, $results );

        foreach( $events as $id => $event )
        {
            if ( $event->Status != self::STATUS_TOSYNC )
            {
                /// @todo !important check that status is an int beteen 1 and 9...
                $results[$event->ID] = $event->Status * -1;
            }
            else
            {
                eZDebug::writeDebug( "Syncing event " . $event->ID . ": object " . $event->ObjectID . ", feed " . $event->TargetID, __METHOD__ );

                // set status to 'pending'
                $event->Status = self::STATUS_SYNCING;
                $event->SyncBeginDate = time();
                $event->store( array( 'status', 'sync_begin_date' ) );

                $transport = $transports[$event->TargetID];
                try
                {
                    $result = $transport->syncEvents( array( $event ) );
                    $result = $result[0];
                }
                catch( exception $e)
                {
                    /// @todo !important use exception error code ?
                    $result = self::ERROR_TRANSPORTEXCEPTION;
                }

                if ( $result != 0 )
                {
                    eZDebug::writeError( "Failed syncing item " . $event->ID . ", transport error code: $result", __METHOD__ );
                    $event->Status = self::STATUS_TOSYNC;
                    $event->SyncBeginDate = null;
                    $event->store();
                    /// @todo !important check that result is an int beteen -100 and -inf
                    $results[$event->ID] = $result;
                }
                else
                {
                    // delete event from db with all its nodes
                    $db = eZDB::instance();
                    $db->begin();
                    $db->query( 'DELETE FROM ezcontentstaging_event_node WHERE event_id = ' . $event->ID );
                    self::removeObject( self::definition(), array( 'id' => $event->ID ) );
                    $db->commit();

                    eZDebug::writeDebug( "Synced item " . $event->ID, __METHOD__ );
                    $results[$event->ID] = 0;
                }
            }
        }

        return $results;
    }

    /**
    * Removes useless events from array if any atre found
    * cases:
    * . a hide+unhide chain (event with other events in the middle)
    * . a setsection chain with no node add/remove/delete/swap in the middle
    * @todo ...
    */
    static protected function coalesceEvents( array &$events, array &$results )
    {

    }

    /// @todo to be augmented (or replaced) with current user perms checking
    /*function canSync( )
    {
        return $this->Status == self::STATUS_TOSYNC;
    }*/

    /**
    * Helper function - returns the list of ndoes an obj related to, saving on resources
    * Funny that something similar not implemented in eZContentObject...
    * @return array node id => path_string
    */
    static function assignedNodeIds( $objectId )
    {
        $db = eZDB::instance();
        $out = array();
        foreach( $db->arrayQuery( "SELECT node_id, path_string from ezcontentobject_tree where contentobject_id = $objectId" ) as $row )
        {
            $out[$row['node_id']] = $row['path_string'];
        }
        return $out;
    }

}

?>
