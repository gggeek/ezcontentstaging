<?php
/**
* The persistent class used to store information about which objects/nodes need to be
* synced to target servers - every content-modifying action gets a line in
* the eZContentStagingItem table for every existing target host.
* For actions affecting a single node, one row is created in the ezcontentstaging_item_nodes table
* (eg: hide/show)
* For actions affecting the whole object (eg: edit), many rows are added in the
* ezcontentstaging_item_nodes table.
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
    const ACTION_ADDLOCATION = 1;
    const ACTION_REMOVELOCATION = 2;
    const ACTION_UPDATESECTION = 4;
    const ACTION_HIDEUNHIDE = 8;

    const STATUS_TOSYNC = 0;
    const STATUS_SYNCING = 1;
    const STATUS_SUSPENDED = 2;

    /// @deprecated
    const SYNC_PUBLICATION = 1;
    const SYNC_DELETION = 2;
    const SYNC_VISIBILITY = 4;
    const SYNC_NODES = 8;
    const SYNC_SECTION = 16;
    const SYNC_STATES = 32;
    const SYNC_SORTORDER = 64;

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
                                         /// @todo rename to 'created' ?
                                         'modified' => array( 'name' => 'Modified',
                                                              'datatype' => 'integer',
                                                              'required' => true ),
                                         // type of event (what to sync)
                                         'to_sync' => array( 'name' => 'ToSync',
                                                             'datatype' => 'integer',
                                                             'required' => true ),
                                         // used to avoid double syncing in parallel
                                         'status' => array( 'name' => 'Status',
                                                            'datatype' => 'integer',
                                                            'default' => 0,
                                                            'required' => true ),
                                         // we store extra data here, eg. description of deleted objects
                                         'data_text' => array( 'name' => 'data_text',
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
        return json_decode( $this->Data, true );
    }

    function getNodes()
    {
        $db = eZDB::instance();
        $nodeids = $db->arrayquery( 'select node_id from ezcontentstaging_item_nodes where ezcontentstaging_item_nodes.item_id = ' . $this->ID, array( 'column' => 'node_id' ) );
        /// @todo log error if count oif nodes found is lesser than stored node ids ?
        return self::fetchObjectList( eZContentObjectTreeNode::definition(),
                                      null,
                                      array( 'node_id' => $nodeids ),
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

    static function fetchByObject( $object_id, $asObject = true )
    {
        return self::fetchObjectList( self::definition(),
                                      null,
                                      array( 'object_id' => $object_id ),
                                      null,
                                      null,
                                      $asObject );
    }

    static function fetchByNode( $nodeId, $objectId=null, $asObject = true )
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
        return self::fetchObjectList( self::definition(),
                                      null,
                                      array( 'object_id' => $objectId ),
                                      null,
                                      null,
                                      $asObject,
                                      /// @todo oracle support: all fields in select list should be in group by
                                      array( 'id' ),
                                      null,
                                      array( 'ezcontentstaging_event_node' ),
                                      ' AND ezcontentstaging_event_node.node_id = ' . (int)$node_id . ' AND ezcontentstaging_event_node.item_id = id' );
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
    static function addEvent( $targetId, $objectId, $action, $data, $nodeIds=array() )
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
            'data_text' => json_encode( $data )
            ) );
        $event->store();
        $id = $event->ID;
        if ( count( $nodeIds ) )
        {
            foreach( $nodeIds as $nodeId )
            {
                $db->query( "insert into ezcontentstaging_event_node( item_id, node_id ) values ( $id, $nodeId )" );
            }
            $db->commit();
        }
    }

    /**
    *
    * @return integer 0 on sucess
    * @todo after 3 consecutive errors, suspend sync?
    */
    static function syncEvent()
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

        return 0;
    }

    /// @todo to be augmented (or replaced) with current user perms checking
    /*function canSync( )
    {
        return $this->Status == self::STATUS_TOSYNC;
    }*/

    /**
    * Helper function. Funny this is not implemented in eZContentObject...
    */
    static function assignedNodeIds( $objectId )
    {
        $db = eZDB::instance();
        return $db->arrayQuery( "SELECT node_id from ezcontentobject_tree where contentobject_id = $objectId", array( 'column' => 'node_id' ) );
    }

}

?>
