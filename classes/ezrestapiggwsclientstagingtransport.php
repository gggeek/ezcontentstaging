<?php
/**
* Class used to sync content to remote servers
* - using ggws extension for the http layer
* - interfacing with the REST api for content creation
*
* @package ezcontentstaging
*
* @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

class eZRestApiGGWSClientStagingTransport extends eZBaseStagingTransport implements eZContentStagingTransport
{
    // so far only used to speed up node checking. remote node node_id => local node node_id
    protected static $remoteNodesNodeIdcache = array();

    function __construct( eZContentStagingTarget $target )
    {
        $this->target = $target;
    }

    function initializeSubtree( eZContentObjectTreeNode $node, $remoteNodeID )
    {
        $nodeID = $node->attribute( 'node_id' );
        $object = $node->attribute( 'object' );
        $initData = array(
            'nodeID' => $nodeID,
            'nodeRemoteID' => $node->attribute( 'remote_id' ),
            'objectRemoteID' => $object->attribute( 'remote_id' ),
            'remoteNodeID' => $remoteNodeID
        );
        $evtID = eZContentStagingEvent::addEvent(
            $this->target->attribute( 'id' ),
            $object->attribute( 'id' ),
            eZContentStagingEvent::ACTION_UPDATEREMOTEIDS,
            $initData,
            array( $nodeID )
        );

        /*if ( $evtID )
        {
            $ok = eZContentStagingEvent::syncEvents( array( $evtID ) );
            $out[] = $ok[$evtID];
        }
        else
        {
            $out[] = 0;
        }*/

        return 0;
    }

    function syncEvents( array $events )
    {
        $results = array();
        foreach ( $events as $event )
        {
            try
            {
                $out = $this->syncEvent( $event );
                $results[] = $out;
            }
            catch ( exception $e )
            {
                $results[] = $e->getMessage();
            }
        }
        return $results;
    }

    /**
     * Excutes a single event synchronization
     * @throws exception on error
     * @todo fix api: either throw exceptions OR return values != 0 on errors
     */
    protected function syncEvent( $event )
    {
        $data = $event->getData();
        switch ( $event->attribute( 'to_sync' ) )
        {
            case eZContentStagingEvent::ACTION_ADDLOCATION:
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );

                $out = $this->restCall(
                    "PUT",
                    /// @todo !important test that $RemoteObjRemoteID is not null
                    "/content/objects/remote/$RemoteObjRemoteID/locations?parentRemoteId=" .
                        $this->buildRemoteId( $data['parentNodeID'] , $data['parentNodeRemoteID'] ),
                    array(
                        'remoteId' => $RemoteNodeRemoteID,
                        'priority' => $data['priority'],
                        'sortField' => eZContentStagingLocation::encodeSortField( $data['sortField'] ),
                        'sortOrder' => eZContentStagingLocation::encodeSortOrder( $data['sortOrder'] )
                    )
                );
                if ( !is_array( $out ) || !is_array( $out['Location'] ) || !isset( $out['Location']['remoteId'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['Location']['remoteId'] != $RemoteNodeRemoteID )
                {
                    throw new Exception( "Remote id of created node does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                return 0;

            case eZContentStagingEvent::ACTION_DELETE:
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $this->restCall( "DELETE", "/content/objects/remote/$RemoteObjRemoteID?trash=" . self::encodeTrash( $data['trash'] ) );
                return 0;

            case eZContentStagingEvent::ACTION_HIDEUNHIDE:
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                $this->restCall( "POST", "/content/locations/remote/$RemoteNodeRemoteID?hide=" . ( $data['hide'] ? 'true' : 'false' ) );
                return 0;

            case eZContentStagingEvent::ACTION_MOVE:
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                $this->restCall(
                    "PUT",
                    "/content/locations/remote/$RemoteNodeRemoteID/parent?destParentRemoteId=" .
                        $this->buildRemoteId( $data['parentNodeID'], $data['parentNodeRemoteID'] )
                );
                return 0;

            case eZContentStagingEvent::ACTION_PUBLISH:
                // this can be either a content creation or update
                // in any way, it is a multi-step process

                // step 1: create new version

                /// @todo what if we create many drafts and we discard them? Is the first version created still 1? test it!
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $syncdate = false;
                // allow incomplete ini not to raise a warning: use @
                if ( @$this->target->attribute( 'use_source_creation_dates_on_target' ) == 'enabled')
                {
                    $syncdate = true;
                }
                if ( $data['version'] == 1 )
                {
                    $url = "/content/objects?parentRemoteId=" . $this->buildRemoteId( $data['parentNodeID'], $data['parentNodeRemoteID'] );
                    $payload = self::encodeObject( $event->attribute( 'object_id' ),  $data['version'], $data['locale'], false, $RemoteObjRemoteID, $syncdate );
                }
                else
                {
                    $url = "/content/objects/remote/$RemoteObjRemoteID/versions";
                    $payload = self::encodeObject( $event->attribute( 'object_id' ),  $data['version'], $data['locale'], true, false, $syncdate );
                }
                if ( !$payload )
                {
                    throw new Exception( "Can not serialize object to be sent", eZContentStagingEvent::ERROR_OBJECTCANNOTSERIALIZE );
                }

                $out = $this->restCall( "POST", $url, $payload );
                if ( !is_array( $out ) || !isset( $out['Location'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }

                // step 2: publish created version
                $array = explode( '/', $out['Location'] );
                $versionNr = end( $array );
                if ( $versionNr == '' )
                {
                    throw new Exception( "Missing version number in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                $out = $this->restCall( "POST", "/content/objects/remote/$RemoteObjRemoteID/versions/$versionNr" );

                // step 3: fix remote id of created node (only for new nodes)
                if ( $data['version'] == 1 )
                {
                    /// @todo test format for $out
                    $array = explode( '/', $out['Location'] );
                    $remoteNodeId = end( $array );
                    if ( $remoteNodeId == '' )
                    {
                        throw new Exception( "Missing node id in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                    }
                    $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    $out = $this->restCall( "PUT", "/content/locations/$remoteNodeId", array( 'remoteId' => $RemoteNodeRemoteID ) );
                    if ( !is_array( $out ) || !isset( $out['remoteId'] ) )
                    {
                        throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                    }
                    if ( $out['remoteId'] != $RemoteNodeRemoteID )
                    {
                        throw new Exception( "node remoteId in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                    }
                }
                return 0;

            case eZContentStagingEvent::ACTION_REMOVELOCATION:
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                $this->restCall( "DELETE", "/content/locations/remote/$RemoteNodeRemoteID?trash=" . self::encodeTrash( $data['trash'] ) );
                return 0;

            case eZContentStagingEvent::ACTION_REMOVETRANSLATION:
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                foreach ( $data['translations'] as $translation )
                {
                    $out = $this->restCall( "DELETE", "/content/objects/remote/$RemoteObjRemoteID/languages/" . self::encodeLanguageId( $translation ) );
                }
                return 0;

            case eZContentStagingEvent::ACTION_SORT:
                $out = $this->restCall(
                    "PUT",
                    "/content/locations/remote/" . $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] ),
                    array(
                        // @todo can we omit safely to send priority?
                        //'priority' => $data['priority'],
                        'sortField' => eZContentStagingLocation::encodeSortField( $data['sortField'] ),
                        'sortOrder' => eZContentStagingLocation::encodeSortOrder( $data['sortOrder'] )
                    )
                );
                if ( !is_array( $out ) || !isset( $out['sortField'] ) || !isset( $out['sortOrder'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['sortField'] != eZContentStagingLocation::encodeSortField( $data['sortField'] ) )
                {
                    throw new Exception( "sortField in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['sortOrder'] != eZContentStagingLocation::encodeSortOrder( $data['sortOrder'] ) )
                {
                    throw new Exception( "sortOrder in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                return 0;

            case 'swap':
                /// @todo ...
                return -333;

            case eZContentStagingEvent::ACTION_UPDATEALWAYSAVAILABLE:
                $out = $this->restCall(
                    "PUT",
                    "/content/objects/remote/" . $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' ),
                    array( 'alwaysAvailable' => self::encodeAlwaysAvailable( $data['alwaysAvailable'] ) )
                );
                if ( !is_array( $out ) || !isset( $out['alwaysAvailable'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['alwaysAvailable'] != self::encodeAlwaysAvailable( $data['alwaysAvailable'] ) )
                {
                    throw new Exception( "alwaysAvailable in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                return 0;

            case eZContentStagingEvent::ACTION_UPDATEINITIALLANGUAGE:
                $out = $this->restCall(
                    "PUT",
                    "/content/objects/remote/" . $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' ),
                    array( 'initialLanguage' => self::encodeLanguageId( $data['initialLanguage'] ) )
                );
                if ( !is_array( $out ) || !isset( $out['initialLanguage'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['initialLanguage'] != self::encodeLanguageId( $data['initialLanguage'] ) )
                {
                    throw new Exception( "initialLanguage in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                return 0;

            case eZContentStagingEvent::ACTION_UPDATEMAINASSIGNMENT:
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                $out = $this->restCall(
                    "PUT",
                    "/content/locations/remote/$RemoteNodeRemoteID",
                    array( 'mainLocationRemoteId' => $RemoteNodeRemoteID )
                );
                if ( !is_array( $out ) || !isset( $out['mainLocationId'] ) || !isset( $out['id'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['mainLocationId'] != $out['id'] )
                {
                    throw new Exception( "mainLocationId in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                return 0;

            case eZContentStagingEvent::ACTION_UPDATEOBJECSTATE:
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $this->restCall( "PUT", "/content/objects/remote/$RemoteObjRemoteID/states", $data['stateList'] );
                return 0;

            case eZContentStagingEvent::ACTION_UPDATEPRIORITY:
                foreach ( $data['priorities'] as $priority )
                {
                    $out = $this->restCall(
                        "PUT",
                        "/content/locations/remote/" . $this->buildRemoteId( $priority['nodeID'], $priority['nodeRemoteID'] ),
                        array( 'priority' => $priority['priority'] )
                    );
                    if ( !is_array( $out ) || !isset( $out['priority'] ) )
                    {
                        throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                    }
                    if ( $out['priority'] != $priority['priority'] )
                    {
                        throw new Exception( "priority in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                    }
                }
                return 0;

            case eZContentStagingEvent::ACTION_UPDATESECTION:
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $this->restCall( "PUT", "/content/objects/remote/$RemoteObjRemoteID/section?sectionId={$data['sectionID']}" );
                return 0;

            case eZContentStagingEvent::ACTION_UPDATEREMOTEIDS:
                // set remote id on remote node and remote object
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );

                /// @todo switch from rest api v1 (content/node) to v2 (content/location)
                $out = $this->restCall( "GET", "/content/locations/{$data['remoteNodeID']}" );
                if ( !is_array( $out ) || !isset( $out['contentId'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                $remoteObjID = $out['contentId'];

                $out = $this->restCall( "PUT", "/content/locations/{$data['remoteNodeID']}", array( 'remoteId' => $RemoteNodeRemoteID ) );
                if ( !is_array( $out ) || !isset( $out['remoteId'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['remoteId'] != $RemoteNodeRemoteID )
                {
                    throw new Exception( "node remoteId in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }

                // nb: this is a non-transactional API: we might succeed in updating
                // node remote id but not object remote id. In such case
                // we do NOT rollback our changes
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $out = $this->restCall( "PUT", "/content/objects/$remoteObjID", array( 'remoteId' => $RemoteObjRemoteID ) );
                if ( !is_array( $out ) || !isset( $out['remoteId'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['remoteId'] != $RemoteObjRemoteID )
                {
                    throw new Exception( "object remoteId in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                return 0;

            default:
                throw new Exception( "Event type " . $event->attribute( 'to_sync' ) . " unknown", eZContentStagingEvent::ERROR_EVENTTYPEUNKNOWNTOTRANSPORT ); // should we store this error code in this class instead?
        }
    }

    function checkNode( eZContentObjectTreeNode $node )
    {
        $out = 0;

        try
        {
            $remote = $this->restCall( "GET", "/content/locations/remote/" . $this->buildRemoteId( $node->attribute( 'node_id' ), $node->attribute( 'remote_id' ) ) );
            if ( !is_array( $remote ) || !isset( $remote['id'] ) || !isset( $remote['parentId'] ) )
            {
                $out = self::DIFF_TRANSPORTERROR;
            }
            else
            {

                $local = (array) new eZContentStagingLocation( $node );

                // check if parents match:
                // is the node_id of remote parent node in cache?
                if ( isset( self::$remoteNodesNodeIdcache[$remote['parentId']] ) )
                {
                    if ( self::$remoteNodesNodeIdcache[$remote['parentId']] != $node->attribute( 'parent_node_id' ) )
                    {
                        $out = $out | self::DIFF_NODE_PARENT;
                    }
                }
                else
                {
                    // fetch remote parent node (as "node linked to local parent node") and check if it's the same as declared by the remote node
                    $parent = $node->attribute( 'parent' );
                    try
                    {
                        $remoteParent = $this->restCall(
                            "GET",
                            "/content/locations/remote/" . $this->buildRemoteId( $parent->attribute( 'node_id' ), $parent->attribute( 'remote_id' ) )
                        );
                        if ( isset( $remoteParent['id'] ) )
                        {
                            if ( $remoteParent['id'] != $remote['parentId'] )
                            {
                                $out = $out | self::DIFF_NODE_PARENT;
                            }
                            self::$remoteNodesNodeIdcache[$remoteParent['id']] = $parent->attribute( 'node_id' );
                        }
                        else
                        {
                            /// @todo
                        }
                    }
                    catch ( exception $e )
                    {
                        /// @todo
                    }
                }

                if ( $local['hidden'] != $remote['hidden'] )
                {
                    $out = $out | self::DIFF_NODE_VISIBILITY;
                }

                if ( $local['sortField'] != $remote['sortField'] )
                {
                    $out = $out | self::DIFF_NODE_SORTFIELD;
                }

                if ( $local['sortField'] != $remote['sortOrder'] )
                {
                    $out = $out | self::DIFF_NODE_SORTORDER;
                }

                // @todo check: children count

                // save remote node local id (on remote server)
                if ( !isset( self::$remoteNodesNodeIdcache[$remote['id']] ) )
                {
                    self::$remoteNodesNodeIdcache[$remote['id']] = $node->attribute( 'node_id' );
                }
            }
        }
        catch ( exception $e )
        {
            if ( self::getHTTPErrorCode( $e ) == '404' )
            {
                $out = self::DIFF_NODE_MISSING;
            }
            else
            {
                $out = self::DIFF_TRANSPORTERROR;
            }
        }
        return $out;
    }

    function checkObject( eZContentObject $object )
    {
        $out = 0;
        try
        {
            $remote = $this->restCall(
                "GET",
                "/content/objects/remote/" . $this->buildRemoteId( $object->attribute( 'id' ), $object->attribute( 'remote_id' ), 'object' )
            );
            if ( !is_array( $remote ) || !isset( $remote['id'] ) )
            {
                $out = self::DIFF_TRANSPORTERROR;
            }
            else
            {
                $local = (array) new eZContentStagingContent( $object );
                if ( $local['contentType'] != $remote['contentType'] )
                {
                    $out = $out & self::DIFF_OBJECT_TYPE;
                }

                if ( $local['sectionId'] != $remote['sectionId'] )
                {
                    $out = $out | self::DIFF_OBJECT_SECTION;
                }

                if ( $local['state'] != $remote['state'] )
                {
                    $out = $out | self::DIFF_OBJECT_STATE;
                }

                if ( $local['alwaysAvailable'] != $remote['alwaysAvailable'] )
                {
                    $out = $out | self::DIFF_OBJECT_ALWAYSAVAILABLE;
                }

                /// @todo check: attributes values, locations, languages, creator id, dates
            }
        }
        catch ( exception $e )
        {
            if ( self::getHTTPErrorCode( $e ) == '404' )
            {
                $out = self::DIFF_OBJECT_MISSING;
            }
            else
            {
                $out = self::DIFF_TRANSPORTERROR;
            }
        }
        return $out;
    }

    /**
     * A helper function used to interact with error responses from ggws client
     */
    protected static function getHTTPErrorCode( exception $e )
    {
        if ( $e->getCode() != ggWebservicesClient::ERROR_NON_200_RESPONSE )
        {
            return null;
        }
        if ( preg_match( '/^HTTP error ([0-9]{3}) /', $e->getMessage(), $matches ) )
        {
            return $matches[1];
        }
        return null;
    }

    /**
     * Wrapper for the REST calls.
     * Throws an exception on any protocol error
     *
     * NB: when eZP is set to debug mode, at leat up to version 4.6, it will
     * not send back proper HTTP response codes (or content type)
     * This means that the callers of restCall() will get back an exception
     * thrown when contacting production servers and a plain array when contacting
     * debug servers. We should probably fix this (or get it fixed server-side)
     *
     * @return mixed
     * @todo implement better result checking and error code parsing
     */
    protected function restCall( $method, $url, $payload=array() )
    {
        if ( !class_exists( 'ggeZWebservicesClient' ) )
        {
            /// @todo !important use a specific exception code
            throw new Exception( "Php class 'ggezwebservicesclient' not found, ggwebservices extension disabled or missing", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
        }

        $options = array( 'method' => $method, 'requestType' => 'application/json' );

        /// @todo test that ggws is enabled and that there is a server defined
        $results = ggeZWebservicesClient::send( $this->target->attribute( 'server' ), $url, $payload, true, $options );
        if ( !isset( $results['result'] ) )
        {
            /// @todo best would be to decode $results['error'], which we get as a string, instead of using a generic error code
            //return eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR;
            throw new Exception( $results['error'], eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
        }
        $response = $results['result'];
        if ( $response->isFault() )
        {
            // currently we have error ranges -101 to -30x here
            //return $response->faultCode();
            throw new Exception( $response->faultString(), $response->faultCode() );
        }

        return $response->value();
    }

    /**
     * Encodes an object's version (single language) to be sent to the remote server
     *
     * @todo move fully to the 'content' external model class ???
     *
     * @todo miss creator_id for updates
     * @todo owner_id for 1st version should be remote id, not plain obj id
     * @todo miss object state info (both create and update)
     *
     */
    protected function encodeObject( $objectID, $versionNr, $locale, $isupdate = false, $RemoteObjRemoteID = false, $syncdate = false )
    {
        $object = eZContentObject::fetch( $objectID );
        if ( !$object )
        {
            eZDebug::writeError( "Cannot encode object $objectID for push to staging server: object not found", __METHOD__ );
            return false;
        }
        $version = $object->version( $versionNr );
        if ( !$version )
        {
            eZDebug::writeError( "Cannot encode object $objectID for push to staging server: version $versionNr not found", __METHOD__ );
            return false;
        }

        $out = array(
            'contentType' => $object->attribute( 'class_identifier' ),
            'fields' => array()
            );

        $ridGenerator = $this->getRemoteIdGenerator();
        $fieldFilter = $this->getFieldFilter();
        foreach ( $version->contentObjectAttributes( $locale ) as $attribute )
        {
            // Note: we always need to send all attributes, even empty ones, as they
            // might have had values in the past, and we need to clear those values
            // (eg: making a string empty that was not empty before)
            /// @todo optimization - for 1st version only send non-empty attributes

            // in case of filter misconfig, play it safe: send no data
            if ( $fieldFilter === false )
            {
                continue;
            }
            elseif( $fieldFilter === true || $fieldFilter->accept( $attribute ) )
            {
                $name = $attribute->attribute( 'contentclass_attribute_identifier' );
                $out['fields'][$name] = (array) new eZContentStagingField( $attribute, $locale, $ridGenerator );
            }
        }

        if ( $isupdate )
        {
            $out['initialLanguage'] = $locale; // initial language of new version
            if ( $syncdate )
            {
                // this is what is shown in template object_information.tpl as "object created" date
                $out['created'] = contentStagingBase::encodeDateTime( $version->attribute( 'created' ) );
            }
        }
        else
        {
            // so far, when sending a version-1 object, the following data are not taken into account:
            // alwaysAvailable, sectionId, ownerId
            $out['initialLanguage'] = $object->attribute( 'initial_language_code' );
            $out['alwaysAvailable'] = $object->attribute( 'always_available' );
            $out['remoteId'] = $RemoteObjRemoteID;
            $out['sectionId'] = $object->attribute( 'section_id' );
            $out['ownerId'] = $object->attribute( 'owner_id' );
            if ( $syncdate )
            {
                $out['created'] = contentStagingBase::encodeDateTime( $object->attribute( 'published' ) );
            }
        }

        return $out;
    }

    static protected function encodeAlwaysAvailable( $value )
    {
        return (bool)$value;
    }

    static protected function encodeTrash( $value )
    {
        return $value ? "true" : "false";
    }

    static protected function encodeLanguageId( $value )
    {
        $lang = eZContentLanguage::fetch( $value );
        return $lang->attribute( 'locale' );
    }

    /**
     * @todo !important implement factory pattern - store $generator for speed
     */
    protected function buildRemoteId( $sourceId, $sourceRemoteId, $type='node' )
    {
        $generator = $this->getRemoteIdGenerator();
        return $generator ? $generator->buildRemoteId( $sourceId, $sourceRemoteId, $type ) : $sourceRemoteId ;
    }

    protected function getRemoteIdGenerator()
    {
        $ini = eZINI::instance( 'contentstagingsource.ini' );
        $targetId = $this->target->attribute( 'id' );
        if ( $ini->hasVariable( "Target_" . $targetId,  'RemoteIdGeneratorClass' ) )
        {
            $class = $ini->variable( "Target_" . $targetId,  'RemoteIdGeneratorClass' );
        }
        else
        {
            $class = 'eZContentStagingSameRemoteIdGenerator';
        }
        if ( !class_exists( $class ) )
        {
            eZDebug::writeError( "Cannot generate remote ids for objects/nodes for target feed $targetId: class $class not found", __METHOD__ );
            return null;
        }
        $generator = new $class( $targetId );
        if ( !is_a( $generator, 'eZContentStagingRemoteIdGenerator' ) )
        {
            eZDebug::writeWarning( "Probable problems ahead generating remote ids for objects/nodes for target feed $targetId: class $class has wrong interface", __METHOD__ );
        }
        return $generator;
    }

    /**
     * @return bool|object true if no filtering is needed, false if there is a  filter class error, or the filter obj instance
     */
    protected function getFieldFilter()
    {
        $ini = eZINI::instance( 'contentstagingsource.ini' );
        $targetId = $this->target->attribute( 'id' );
        $class = '';
        if ( $ini->hasVariable( "Target_" . $targetId,  'FieldFilterClass' ) )
        {
            $class = $ini->variable( "Target_" . $targetId,  'FieldFilterClass' );
        }
        if ( $class == '' )
        {
            return true;
        }
        if ( !class_exists( $class ) )
        {
            eZDebug::writeError( "Cannot filter fields when serializing for target feed $targetId: class $class not found", __METHOD__ );
            return false;
        }
        /// @todo check for interface
        $filter = new $class( $targetId );
        if ( !is_a( $filter, 'eZContentStagingFieldFilter' ) )
        {
            eZDebug::writeWarning( "Probable problems ahead filtering fields when serializing for target feed $targetId: class $class has wrong interface", __METHOD__ );
        }
        return $filter;
    }

    /**
     * These tests should be pushed down into ggeZWebservicesClient, but while we
     * wait for it, we do them here
     */
    function checkConfiguration()
    {
        $out = array();

        $server = @$this->target->attribute( 'server' );
        if ( $server == null )
        {
            $out[] = "Remote server name not set in file contentstagingsource.ini, block 'Target_{$this->target->attribute( 'id' )}', parameter 'Server'";
            return $out;
        }

        $ini = eZINI::instance( 'wsproviders.ini' );
        if ( !$ini->hasGroup( $server ) )
        {
            $out[] = "Remote server name '$server' set in file contentstagingsource.ini, block 'Target_{$this->target->attribute( 'id' )}', parameter 'Server', not defined in wsproviders.ini, block '$server'";
            return $out;
        }

        if ( !$ini->hasVariable( $server, 'providerType' ) || $ini->variable( $server, 'providerType' ) != 'REST' )
        {
            $out[] = "Remote server named '$server' has wrong type, should be 'REST' in file wsproviders.ini, block '$server', parameter 'providerType'";
        }
        if ( !$ini->hasVariable( $server, 'providerUri' ) || $ini->variable( $server, 'providerUri' ) == '' )
        {
            $out[] = "Remote server named '$server' has no URL set in file wsproviders.ini, block '$server', parameter 'providerUri'";
        }

        /// @todo we could test that ggws is enabled...

        return $out;
    }

    /**
     * NB: what if later on we add API version numbers like 1.1 or 1.0.1?
     */
    function checkConnection()
    {
        $out = array();

        $url = "/api/versions";
        $ini = eZINI::instance( 'wsproviders.ini' );
        $uri = $ini->variable( $this->target->attribute( 'server' ), 'providerUri' );
        try
        {
            $resp = $this->restCall( "GET", $url );
            if ( !is_array( $resp ) )
            {

                $out[] = "Invalid response received from target server, expected json array at url $uri$url";
            }
            else
            {
                $found = false;
                foreach ( $resp as $version )
                {
                    if ( @$version['version'] == 1 )
                    {
                        $found = true;
                        break;
                    }
                }
                if ( !$found )
                {
                    $out[] = "Target server does not expose correct API version at url $uri$url";
                }
            }
        }
        catch ( exception $e )
        {
            $out[] = $e->getMessage();
        }


        return $out;
    }

    function checkSubtreeInitialization( eZContentObjectTreeNode $node, $remoteNodeID )
    {
        $out = array();

        try
        {
            $method = 'GET';
            $url = "/content/locations/{$remoteNodeID}";
            $resp = $this->restCall( $method, $url );
            if ( !is_array( $resp ) || !isset( $resp['remoteId'] ) || !isset( $resp['contentId'] ) )
            {
                $out[] = "Received invalid data in response (checking remote node $remoteNodeID)";
            }
            else
            {
                $method = 'GET';
                $url = "/content/objects/{$resp['contentId']}";
                $resp2 = $this->restCall( $method, $url );
                if ( !is_array( $resp2 ) || !isset( $resp2['remoteId'] ) )
                {
                    $out[] = "Received invalid data in response (checking remote object {$resp['contentId']})";
                }
                else
                {
                    $RemoteNodeRemoteID = $this->buildRemoteId( $node->attribute( 'node_id' ), $node->attribute( 'remote_id' ) );
                    $obj = $node->attribute( 'object' );
                    $RemoteObjRemoteID = $this->buildRemoteId( $obj->attribute( 'id' ), $obj->attribute( 'remote_id' ), 'object' );
                    if ( $RemoteNodeRemoteID != $resp['remoteId'] || $RemoteObjRemoteID != $resp2['remoteId'] )
                    {
                        $out[] = "Remote node $remoteNodeID needs synchronization";
                    }
                }
            }
        }
        catch ( exception $e )
        {
            /// @todo we should distinguish between "remote node not found" and "page nout found"
            $out[] = $e->getMessage() . "  (checking remote node $remoteNodeID)";
        }
        return $out;
    }
}
