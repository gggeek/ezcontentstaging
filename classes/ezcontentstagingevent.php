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
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 */

class eZContentStagingEvent extends eZPersistentObject
{
    const ACTION_ADDLOCATION = 1;
    const ACTION_DELETE = 2;
    const ACTION_HIDEUNHIDE = 4;
    const ACTION_MOVE = 8;
    const ACTION_PUBLISH = 16;
    const ACTION_REMOVELOCATION = 32;
    const ACTION_REMOVETRANSLATION = 64;
    const ACTION_SORT = 128;
    const ACTION_SWAP = 256;
    const ACTION_UPDATEALWAYSAVAILABLE = 1024;
    const ACTION_UPDATEINITIALLANGUAGE = 2048;
    const ACTION_UPDATEMAINASSIGNMENT = 4096;
    const ACTION_UPDATEOBJECSTATE = 8192;
    const ACTION_UPDATEPRIORITY = 16384;
    const ACTION_UPDATESECTION = 32768;
    // the 'initializenode' event is used to inject remote_node_id and
    // remote_object_id into the remote node (known by its node_id)
    const ACTION_UPDATEREMOTEIDS = 65536;
    const ACTION_RESTOREFROMTRASH = 131072;

    static $syncStrings = array(
        self::ACTION_ADDLOCATION => 'location added',
        self::ACTION_DELETE => 'object removed',
        self::ACTION_HIDEUNHIDE => 'node hidden/shown',
        self::ACTION_MOVE => 'node moved',
        self::ACTION_PUBLISH => 'object published',
        self::ACTION_REMOVELOCATION => 'location removed',
        self::ACTION_REMOVETRANSLATION => 'translation removed',
        self::ACTION_SORT => 'sort order changed',
        self::ACTION_SWAP => 'two objects swapped',
        self::ACTION_UPDATEALWAYSAVAILABLE => 'alwaysavailable updated',
        self::ACTION_UPDATEINITIALLANGUAGE => 'main language updated',
        self::ACTION_UPDATEMAINASSIGNMENT => 'main location changed',
        self::ACTION_UPDATEOBJECSTATE => 'content state changed',
        self::ACTION_UPDATEPRIORITY => 'child priority changed',
        self::ACTION_UPDATESECTION => 'section changed',
        self::ACTION_RESTOREFROMTRASH => 'object restored from trash',
    );

    const STATUS_TOSYNC = 0;
    const STATUS_SYNCING = 1;
    const STATUS_SUSPENDED = 2;
    const STATUS_SCHEDULED = 4;

    // error ranges:
    // event status ko: -1 to -9
    // event class errors: -10 to -99
    // transport class errors: -100 to -1000

    /// config error: php class for transport not found
    const ERROR_NOTRANSPORTCLASS = -10;
    /// transport class threw an exception
    const ERROR_TRANSPORTEXCEPTION = -11;
    /// config error: source node for feed not found in content
    const ERROR_NOSOURCENODE = -12;
    /// config error: remote source node for feed not specified
    const ERROR_NOREMOTESOURCE = -13;
    /// config error: target feed for event not found
    const ERROR_NOTARGETDEFINED = -14;
    // an error that should never be returned
    const ERROR_BADPHPCODING = -99;

    // nb: rest layer uses errors from -101 to -30x currently, we try to
    // avoid collitions
    const ERROR_EVENTTYPEUNKNOWNTOTRANSPORT = -501;
    const ERROR_OBJECTCANNOTSERIALIZE = -502;
    const ERROR_GENERICTRANSPORTERROR = -503;

    static public function definition()
    {
        return array(
            'fields' => array(
                'id' => array(
                    'name' => 'ID',
                    'datatype' => 'integer',
                    'required' => true
                ),
                'target_id' => array(
                    'name' => 'TargetID',
                    'datatype' => 'string',
                    'required' => true
                ),
                'object_id' => array(
                     'name' => 'ObjectID',
                     'datatype' => 'integer',
                     'required' => true,
                     'foreign_class' => 'eZContentObject',
                     'foreign_attribute' => 'id',
                     'multiplicity' => '1..*'
                ),
                // this is only stored for some events. NULL == affects all languages
                'language_mask' => array(
                    'name' => 'LanguageMask',
                    'datatype' => 'integer',
                    'required' => false
                ),
                // type of event (what to sync)
                'to_sync' => array(
                    'name' => 'ToSync',
                    'datatype' => 'integer',
                    'required' => true
                ),
                // we store a custom modification date of object, as it includes metadata modifications
                /// @todo rename to 'created' ?
                'modified' => array(
                    'name' => 'Modified',
                    'datatype' => 'integer',
                    'required' => true
                ),
                // we store extra data here, eg. description of deleted objects
                'data_text' => array(
                    'name' => 'DataText',
                    'datatype' => 'text'
                ),

                // used to avoid double syncing in parallel
                'status' => array(
                    'name' => 'Status',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => true
                ),
                'sync_begin_date' => array(
                    'name' => 'SyncBeginDate',
                    'datatype' => 'integer',
                    'required' => false,
                    'default' => null
                ),
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
             'function_attributes' => array(
                 'object' => 'getObject',
                 'target' => 'getTarget',
                 //'can_sync' => 'canSync',
                 'nodes' => 'getNodes',
                'trash_nodes' => 'getTrashNodes',
                'node_ids' => 'getNodeIds',
                 'data' => 'getData',
                 'language' => 'language',
                 'to_sync_string' => 'getSyncString'
             ),
             'class_name' => 'eZContentStagingEvent',
             'sort' => array( 'id' => 'asc' ),
             'grouping' => array(), // only there to prevent a php warning by ezpo
             'name' => 'ezcontentstaging_event'
        );
    }

    // ***  function attributes ***

    /**
     * Returns content object that this sync event refers to.
     * In case obj has been deleted, returns data that was stored at time of its
     * deletion (which is not a complete obj, but has some of its data: name, etc...)
     * @return eZContentObject
     */
    public function getObject()
    {
        $return = eZContentObject::exists( $this->ObjectID ) ? eZContentObject::fetch( $this->ObjectID ) : null;
        if ( !$return )
        {
            // obj has been deleted, and we should have some obj data stored within the event
            $data = $this->getData();
            if ( isset( $data['object'] ) )
            {
                $return = $data['object'];
            }
            else
            {
                eZDebug::writeDebug( "Object " . $this->ObjectID . " gone amiss for sync event " . $this->ID, __METHOD__ );
            }
        }
        return $return;
    }

    /// @return eZContentStagingTarget
    public function getTarget()
    {
        /// @todo log error if target gone amiss
        return eZContentStagingTarget::fetch( $this->TargetID );
    }

    /// @return array
    public function getData()
    {
        return json_decode( $this->DataText, true );
    }

    public function getNodeIds()
    {
        $db = eZDB::instance();
        $nodeIds = $db->arrayquery(
            'SELECT node_id FROM ezcontentstaging_event_node WHERE ezcontentstaging_event_node.event_id = ' . $this->ID,
            array( 'column' => 'node_id' )
            );
        // should never happen with current sync events
        if ( !count( $nodeIds ) )
        {
            eZDebug::writeWarning( "No nodes found for sync event " . $this->ID, __METHOD__ );
        }
        return $nodeIds;
    }

    public function getNodes()
    {
        return $this->getNodesByType();
    }

    public function getTrashNodes()
    {
        return $this->getNodesByType( 'trash' );
    }

    protected function getNodesByType( $type='' )
    {
        $nodeIds = $this->getNodeIds();
        // be tolerant of degenerate cases (avoid broken queries)
        if ( !count( $nodeIds ) )
        {
            return array();
        }
        if ( $type == 'trash' )
        {
            $def = eZContentObjectTrashNode::definition();
        }
        else
        {
            $def = eZContentObjectTreeNode::definition();
        }
        $nodes = self::fetchObjectList(
            $def,
            null,
            array( 'node_id' => array( $nodeIds ) ),
            null,
            null
        );
        return $nodes;
    }

    public function language()
    {
        if ( $this->LanguageMask == null )
        {
            return null;
        }
        return eZContentLanguage::fetch( $this->LanguageMask );
    }

    /**
     * Returns a stings, with a comma separated list of the changes happened with this event
     */
    public function getSyncString()
    {
        $out = array();
        $bitmask = (int)$this->ToSync;
        foreach ( self::$syncStrings as $key => $val )
        {
            if ( $bitmask & $key )
            {
                $out[] = $val;
            }
        }
        return implode( ', ', $out );
    }

    // *** fetches ***

    /**
     * fetch a specific sync event
     * @return eZContentStagingEvent or null
     */
    static public function fetch( $id, $asObject = true )
    {
        return self::fetchObject( self::definition(), null, array( 'id' => $id ), $asObject );
    }

    /**
     * Fetch all pending events for a given object, optionally filtered by feed
     * and by event type.
     * @todo refactor: asObject as last param
     */
    static public function fetchByObject( $objectId, $targetId = null, $toSync = null, $asObject = true, $language = null )
    {
        $conds = array( 'object_id' => $objectId );
        if ( $targetId != null )
        {
            $conds['target_id'] = $targetId;
        }
        if ( $toSync != null )
        {
            $conds['to_sync'] = $toSync;
        }
        $customConds = null;
        if ( $language != null )
        {
            $customConds = ' AND ' . self::languagesSQLFilter( $language );
        }
        return self::fetchObjectList( self::definition(), null, $conds, null, null, $asObject, false, null, null, $customConds );
    }

    /**
     * Fetch all pending events for a given node, optionally filtered by feed.
     * 2nd param is there for optimal resource usage
     * @return array of eZContentStagingEvent
     * @todo refactor: asObject as last param
     */
    static public function fetchByNode( $nodeId, $objectId = null, $targetId = null, $asObject = true, $language = null )
    {
        if ( $objectId == null )
        {
            $node = eZContentObjectTreeNode::fetch( $nodeId );
            if ( !$node )
            {
                eZDebug::writeWarning( "Node " . $nodeId . " does not exist", __METHOD__ );
                return null;
            }
            $objectId = $node->attribute( 'contentobject_id' );
        }
        $conds = array( 'object_id' => $objectId );
        if ( $targetId != null )
        {
            $conds['target_id'] = $targetId;
        }
        $customConds = ' AND id IN ( SELECT event_id FROM ezcontentstaging_event_node WHERE node_id = ' . (int)$nodeId . ' )';
        if ( $language != null )
        {
            $customConds .= ' AND ' . self::languagesSQLFilter( $language );
        }
        //$fields = self::definition();
        //$fields = array_keys( $fields['fields'] );
        return self::fetchObjectList( self::definition(), null, $conds, null, null, $asObject, null, null, null, $customConds );
    }

    /**
     * Fetch all pending events for a given node, return them grouped in an
     * array where the key is the feed id
     * @return array of array of eZContentStagingEvent
     */
    static public function fetchByNodeGroupedByTarget( $nodeId, $objectId = null, $language = null )
    {
        $targets = array();
        foreach ( self::fetchByNode( $nodeId, $objectId, null, true, $language ) as $event )
        {
            $targets[$event->TargetID][] = $event;
        }
        return $targets;
    }

    /**
     * @todo refactor: asObject as last param
     */
    static public function fetchListGroupedByObject( $targetId = null, $asObject = true, $offset = null, $limit = null, $language = null )
    {
        $conditions = array();
        if ( $targetId != '' )
        {
            $conditions = array( 'target_id' => $targetId );
        }
        $limits = array();
        if ( $offset !== null )
        {
            $limits['offset'] = $offset;
        }
        if ( $limit != null )
        {
            $limits['limit'] = $limit;
        }
        $customConds = null;
        if ( $language != null )
        {
            if ( $conditions )
            {
                $customConds = ' AND ';
            }
            else
            {
                $customConds = ' WHERE ';
            }
            $customConds .= self::languagesSQLFilter( $language );
        }
        $out = array();
        foreach (
            self::fetchObjectList(
                self::definition(), array( 'object_id' ), $conditions, array(), $limits, false, array( 'object_id' ), null, null, $customConds
           ) as $row
        )
        {
            $conditions['object_id'] = $row['object_id'];
            $out[$row['object_id']] = self::fetchObjectList(
                self::definition(),
                null,
                $conditions,
                null, /// @todo sort by id
                null,
                $asObject,
                false,
                null,
                null,
                $customConds
            );
        }
        return $out;
    }

    /**
     * Fetch all events that need to be synced to a given feed (or all of them)
     * @return array of eZContentStagingEvent
     *
     * @todo refactor: asObject as last param
     */
    static public function fetchList( $targetId = null, $asObject = true, $offset = null, $limit = null, $language = null, $status = null )
    {
        $conditions = array();
        if ( $targetId != '' )
        {
            $conditions = array( 'target_id' => $targetId );
        }
        if ( $status !== null )
        {
            $conditions['status'] = (int)$status;
        }
        $limits = array();
        if ( $offset !== null )
        {
            $limits['offset'] = $offset;
        }
        if ( $limit != null )
        {
            $limits['limit'] = $limit;
        }
        $customConds = null;
        if ( $language != null )
        {
            if ( $conditions )
            {
                $customConds = ' AND ';
            }
            else
            {
                $customConds = ' WHERE ';
            }
            $customConds .= self::languagesSQLFilter( $language );
        }
        return self::fetchObjectList( self::definition(), null, $conditions, null, $limits, $asObject, false, null, null, $customConds );
    }


    /**
     * Fetch all items that need to be synced to a given server (or all of them)
     * en send back only one event per ObjectId and event category
     * @return array of eZContentStagingEvent
     *
     * @todo refactor: asObject as last param
     * /
    static function fetchSumUpList( $target_id=false, $asObject = true, $offset = false, $limit = false )
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
        $syncItems = self::fetchObjectList( self::definition(),
                                      null,
                                      $conditions,
                                      null,
                                      $limits,
                                      $asObject );

        $cleanSyncItems = array();
        $existSyncEvent = array();
        foreach ($syncItems as $syncKey => $syncItem)
        {
            if (!in_array($syncItem->ObjectID.'-'.$syncItem->ToSync, $existSyncEvent))
            {
                array_push($existSyncEvent, $syncItem->ObjectID.'-'.$syncItem->ToSync);
                array_push($cleanSyncItems, $syncItem);
            }
            else
            {
                eZDebug::writeDebug('Event already exists for ObjectID = '.$syncItem->ObjectID.' & Syncing Event ID = '.$syncItem->ToSync, __METHOD__);
            }

        }
        return $cleanSyncItems;
    }*/

    /**
     * Returns count of events to sync to a given server
     * @return integer
     */
    static public function fetchListCount( $targetId = null, $language = null, $status = null )
    {
        $conditions = array();
        if ( $targetId != '' )
        {
            $conditions = array( 'target_id' => $targetId );
        }
        if ( $status !== null )
        {
            $conditions['status'] = (int)$status;
        }
        $customConds = null;
        if ( $language != null )
        {
            if ( $conditions )
            {
                $customConds = ' AND ';
            }
            else
            {
                $customConds = ' WHERE ';
            }
            $customConds .= self::languagesSQLFilter( $locale );
        }

        $rows = self::fetchObjectList(
            self::definition(),
            array(),
            $conditions,
            array(),
            null,
            false,
            null,
            array( array( 'operation' => 'COUNT( * )', 'name' => 'row_count' ) ),
            null,
            $customConds
        );
        return $rows[0]['row_count'];
    }


    static protected function languagesSQLFilter( $language )
    {
        // in case of unknown languages $mask will be 0
        $mask = eZContentLanguage::maskByLocale( $language );
        if ( eZDB::instance()->databaseName() == 'oracle' )
        {
            $maskcondition = "bitand( language_mask, $mask ) > 0";
        }
        else
        {
            $maskcondition = "language_mask & $mask > 0";
        }
        return '( language_mask IS NULL OR ( '. $maskcondition .' ) )';
    }

    // *** other actions ***

    /**
     * Adds an event to the queue.
     *
     * @return integer id of the event created or null if event was filtered out
     * @todo add intelligent deduplication, eg: if there is an hide event then a show one,
     *       do not add show but remove hide, etc...
     */
    static public function addEvent( $targetId, $objectId, $action, $data, $nodeIds = array(), $langMask = null )
    {
        $event = new eZContentStagingEvent(
            array(
                'target_id' => $targetId,
                'object_id' => $objectId,
                'modified' => time(),
                'to_sync' => $action,
                'data_text' => json_encode( $data ),
                'language_mask' => $langMask
            )
        );

        // allow filtering to happen
        $ini = eZINI::instance( 'contentstagingsource.ini' );
        if ( $ini->hasVariable( 'Target_' . $targetId, 'EventCreationFilters' ) )
        {
            foreach ( $ini->variable( 'Target_' . $targetId, 'EventCreationFilters' ) as $filterClass )
            {
                if ( !class_exists( $filterClass ) || !is_subclass_of( $filterClass, 'eZContentStagingEventCreationFilter' ) )
                {
                    eZDebug::writeError(
                        "Class $filterClass not found or not exposing correct interface, can not use as event creation filter",
                        __METHOD__
                    );
                    continue;
                }
                $filter = new $filterClass();
                if ( !$filter->accept( $event, $nodeIds ) )
                {
                    return null;
                }
            }
        }

        if ( !empty( $nodeIds ) )
        {
            $db = eZDB::instance();
            $db->begin();
        }
        $event->store();
        $id = $event->ID;
        if ( !empty( $nodeIds ) )
        {
            foreach ( $nodeIds as $nodeId )
            {
                $db->query( "insert into ezcontentstaging_event_node( event_id, node_id ) values ( $id, $nodeId )" );
            }
            $db->commit();
        }
        return $id;
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
    static public function syncEvents( array $events, $iterator=null )
    {
        $results = array();

        // optimize usage of transport objects: build only one per target
        $transports = array();
        foreach ( $events as $id => $event )
        {
            $target = eZContentStagingTarget::fetch( $event->TargetID );
            if ( !$target )
            {
                eZDebug::writeError(
                    "Can not sync event " . $event->ID . " to target " . $event->TargetID . ", target not found",
                    __METHOD__
                );
                //$results[$event->ID] = self::ERROR_NOTARGETDEFINED;
                $results[$event->ID] = "Can not sync event: its target feed is missing";
                unset( $events[$id] );
                continue;
            }
            $class = $target->attribute( 'transport_class' );
            if ( !isset( $transports[$event->TargetID] ) )
            {
                if ( !class_exists( $class ) )
                {
                    eZDebug::writeError(
                       "Can not sync event " . $event->ID . " to target " . $event->TargetID . ", class $class not found",
                        __METHOD__
                    );
                    //$results[$event->ID] = self::ERROR_NOTRANSPORTCLASS;
                    $results[$event->ID] = "Can not sync event: its target feed transport class is missing";
                    unset( $events[$id] );
                    continue;
                }
                $transports[$event->TargetID] = new $class( $target );
            }
            $results[$event->ID] = self::ERROR_BADPHPCODING; // this has to updated before we return
        }

        // coalescing events allows to send fewer of them
        self::coalesceEvents( $events, $results );

        foreach ( $events as $event )
        {
            if ( $event->Status != self::STATUS_TOSYNC )
            {
                /// @todo !important check that status is an int beteen 1 and 9...
                $results[$event->ID] = "Can not sync event: not in pending status. Status: " . $event->Status;
            }
            else
            {
                eZDebug::writeDebug(
                    "Syncing event " . $event->ID . ": object " . $event->ObjectID . ", feed " . $event->TargetID,
                    __METHOD__
                );

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
                catch( exception $e )
                {
                    /// @todo !important use exception error code ?
                    //$result = self::ERROR_TRANSPORTEXCEPTION;
                    $result = $e->getMessage();
                }

                if ( $result !== 0 )
                {
                    eZDebug::writeError( "Failed syncing event " . $event->ID . ", transport error code: $result", __METHOD__ );
                    $event->abortSync();
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

                    eZDebug::writeDebug( "Synced event " . $event->ID, __METHOD__ );
                    $results[$event->ID] = 0;
                }
            }

            if ( is_callable( $iterator ) )
            {
                call_user_func( $iterator );
            }
        }

        return $results;
    }

    /**
     * Updates event status (in db) back to "to be synchronized"
     */
    public function abortSync()
    {
        $this->Status = self::STATUS_TOSYNC;
        $this->SyncBeginDate = null;
        $this->store();
    }

    /**
     * Removes a list of events given their ids
     * @return integer number of deleted events
     *
     * @todo return real number of deleted events - this is not really atomic...
     */
    static public function removeEvents( $eventIDList, $alsoSyncing = false )
    {
        $db = eZDB::instance();
        if ( !$alsoSyncing )
        {
            // we first filter out any events in syncing status
            $eventIDList = $db->arrayquery(
                'SELECT id FROM ezcontentstaging_event WHERE status <> ' . self::STATUS_SYNCING . ' AND ' .
                $db->generateSQLINStatement( $eventIDList, 'id', false, true, 'integer' ),
                array( 'column' => 'id' )
            );
        }
        $out = count( $eventIDList );
        if ( $out )
        {
            $db->begin();
            $db->query( "DELETE FROM ezcontentstaging_event_node WHERE " . $db->generateSQLINStatement( $eventIDList, 'event_id', false, true, 'integer' ) );
            $db->query( "DELETE FROM ezcontentstaging_event WHERE " . $db->generateSQLINStatement( $eventIDList, 'id', false, true, 'integer' ) );
            $db->commit();
        }
        return $out;
    }

    /**
     * Removes a list of events given their targets ids
     * @return integer number of deleted events
     *
     * @todo return real number of deleted events - this is not really atomic...
     */
    static public function removeEventsByTargets( $targetIDList, $alsoSyncing = false )
    {
        $db = eZDB::instance();
        foreach ( $targetIDList as $key => $val )
        {
            $targetIDList[$key] = "'" . $db->escapeString( $val ) . "'";
        }
        if ( !$alsoSyncing )
        {
            // we first filter out any events in syncing status
            $eventIDList = $db->arrayquery(
                'SELECT id FROM ezcontentstaging_event WHERE status <> ' . self::STATUS_SYNCING . ' AND ' .
                $db->generateSQLINStatement( $targetIDList, 'target_id', false, true ),
                array( 'column' => 'id' )
            );
        }
        else
        {
            $eventIDList = $db->arrayquery(
                'SELECT id FROM ezcontentstaging_event WHERE ' . $db->generateSQLINStatement( $targetIDList, 'target_id', false, true ),
                array( 'column' => 'id' ) );
        }
        $out = count( $eventIDList );
        if ( $out )
        {
            $db->begin();
            $db->query( "DELETE FROM ezcontentstaging_event_node WHERE " . $db->generateSQLINStatement( $eventIDList, 'event_id', false, true, 'integer' ) );
            $db->query( "DELETE FROM ezcontentstaging_event WHERE " . $db->generateSQLINStatement( $eventIDList, 'id', false, true, 'integer' ) );
            $db->commit();
        }
        return $out;
    }

    /**
     * Removes useless events from array if any are found
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
     * Helper function - returns the list of ndoes an obj relates to, saving on resources
     * Funny that something similar not implemented in eZContentObject...
     * @return array of string node_id => path_string
     */
    static public function assignedNodeIds( $objectId )
    {
        $db = eZDB::instance();
        $out = array();
        foreach ( $db->arrayQuery( "SELECT node_id, path_string from ezcontentobject_tree where contentobject_id = $objectId" ) as $row )
        {
            $out[$row['node_id']] = $row['path_string'];
        }
        return $out;
    }

}
