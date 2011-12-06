<?php
/**
* Class used to sync content to remote servers
* - using ggws extension for the http layer
* - interfacing with the REST api for content creation
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

class eZRestApiGGWSClientStagingTransport implements eZContentStagingTransport
{
    // so far only used to speed up node checking. remote node node_id => local node node_id
    protected static $remoteNodesNodeIdcache = array();

    function __construct( eZContentStagingTarget $target )
    {
        $this->target = $target;
    }

    function syncEvents( array $events )
    {
        $results = array();
        foreach( $events as $event )
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
        switch( $event->attribute( 'to_sync' ) )
        {
            case eZContentStagingEvent::ACTION_ADDLOCATION:
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                $RemoteParentNodeRemoteID = $this->buildRemoteId( $data['parentNodeID'] , $data['parentNodeRemoteID'] );
                /// @todo !important test that $RemoteObjRemoteID is not null
                $method = 'PUT';
                $url = "/content/objects/remote/$RemoteObjRemoteID/locations?parentRemoteId=$RemoteParentNodeRemoteID";
                $payload = array(
                    'remoteId' => $RemoteNodeRemoteID,
                    'priority' => $data['priority'],
                    'sortField' => eZContentStagingLocation::encodeSortField( $data['sortField'] ),
                    'sortOrder' => eZContentStagingLocation::encodeSortOrder( $data['sortOrder'] )
                    );
                $out = $this->restCall( $method, $url, $payload );
                if ( !is_array( $out ) || !isset( $out['remoteId'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['remoteId'] != $RemoteNodeRemoteID )
                {
                    throw new Exception( "Remote id of created node does not match waht was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                return 0;

            case eZContentStagingEvent::ACTION_DELETE:
                $method = 'DELETE';
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $url = "/content/objects/remote/$RemoteObjRemoteID?trash={$data['trash']}";
                $this->restCall( $method, $url );
                return 0;

            case eZContentStagingEvent::ACTION_HIDEUNHIDE:
                $method = 'POST';
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                $url = "/content/locations/remote/$RemoteNodeRemoteID?hide=" . ( $data['hide'] ? 'true' : 'false' );
                $this->restCall( $method, $url );
                return 0;

            case eZContentStagingEvent::ACTION_MOVE:
                $method = 'PUT';
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                $RemoteParentNodeRemoteID = $this->buildRemoteId( $data['parentNodeID'], $data['parentNodeRemoteID'] );
                $url = "/content/locations/remote/$RemoteNodeRemoteID/parent?destParentRemoteId=$RemoteParentNodeRemoteID";
                $this->restCall( $method, $url );
                return 0;

            case eZContentStagingEvent::ACTION_PUBLISH:
                // this can be either a content creation or update
                // in any way, it is a multi-step process

                // step 1: create new version

                /// @todo what if we create many drafts and we discard them? Is the first version created still 1? test it!
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $syncdate = false;
                if ( $this->target->attribute( 'use_source_creation_dates_on_target' ) == 'enabled')
                {
                    $syncdate = true;
                }
                if ( $data['version'] == 1 )
                {
                    $method = 'POST';
                    $RemoteParentNodeRemoteID = $this->buildRemoteId( $data['parentNodeID'], $data['parentNodeRemoteID'] );
                    $url = "/content/objects?parentRemoteId=$RemoteParentNodeRemoteID";
                    $payload = self::encodeObject( $event->attribute( 'object_id' ),  $data['version'], $data['locale'], false, $RemoteObjRemoteID, $syncdate );
                }
                else
                {
                    $method = 'POST';
                    $url = "/content/objects/remote/$RemoteObjRemoteID/versions";
                    $payload = self::encodeObject( $event->attribute( 'object_id' ),  $data['version'], $data['locale'], true, false, $syncdate );
                }
                if ( !$payload )
                {
                    throw new Exception( "Can not serialize object to be sent", eZContentStagingEvent::ERROR_OBJECTCANNOTSERIALIZE );
                }

                $out = $this->restCall( $method, $url, $payload );
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
                $method = 'POST';
                $url = "/content/objects/remote/$RemoteObjRemoteID/versions/$versionNr";
                $out = $this->restCall( $method, $url );

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
                    $method = 'PUT';
                    $url = "/content/locations/$remoteNodeId";
                    $payload = array(
                        'remoteId' => $RemoteNodeRemoteID
                    );
                    $out = $this->restCall( $method, $url, $payload );
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
                $method = 'DELETE';
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                $url = "/content/locations/remote/$RemoteNodeRemoteID?trash=" . self::encodeTrash( $data['trash'] );
                $this->restCall( $method, $url );
                return 0;

            case eZContentStagingEvent::ACTION_REMOVETRANSLATION:
                $method = 'DELETE';
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $baseurl = "/content/objects/remote/$RemoteObjRemoteID/languages/";
                foreach ( $data['translations'] as $translation )
                {
                    $url = $baseurl . self::encodeLanguageId( $translation );
                    $out = $this->restCall( $method, $url );
                    if ( $out !== 0 )
                    {
                        /// @todo shall we break here or what? we only removed a few languages, not all of them...
                        return $out;
                    }
                }
                break;

            case eZContentStagingEvent::ACTION_UPDATESECTION:
                $method = 'PUT';
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $url = "/content/objects/remote/$RemoteObjRemoteID/section?sectionId={$data['sectionID']}";
                $this->restCall( $method, $url );
                return 0;

            case eZContentStagingEvent::ACTION_SORT:
                $method = 'PUT';
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                $url = "/content/locations/remote/$RemoteNodeRemoteID";
                $payload = array(
                    // @todo can we omit safely to send priority?
                    //'priority' => $data['priority'],
                    'sortField' => eZContentStagingLocation::encodeSortField( $data['sortField'] ),
                    'sortOrder' => eZContentStagingLocation::encodeSortOrder( $data['sortOrder'] )
                    );
                $out = $this->restCall( $method, $url, $payload );
                if ( !is_array( $out ) || !isset( $out['sortField'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['sortField'] != eZContentStagingLocation::encodeSortField( $data['sortField'] ) )
                {
                    throw new Exception( "sortField in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                return 0;

            case 'swap':
                /// @todo ...
                return -333;

            case eZContentStagingEvent::ACTION_UPDATEALWAYSAVAILABLE:
                $method = 'PUT';
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $url = "/content/objects/remote/$RemoteObjRemoteID";
                $payload = array( 'alwaysAvailable' => self::encodeAlwaysAvailable( $data['alwaysAvailable'] ) );
                $out = $this->restCall( $method, $url, $payload );
                if ( !is_array( $out ) || isset( $out['alwaysAvailable'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['alwaysAvailable'] != self::encodeAlwaysAvailable( $data['alwaysAvailable'] ) )
                {
                    throw new Exception( "alwaysAvailable in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                return 0;

            case eZContentStagingEvent::ACTION_UPDATEINITIALLANGUAGE:
                $method = 'PUT';
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $url = "/content/objects/remote/$RemoteObjRemoteID";
                $payload = array( 'initialLanguage' => self::encodeLanguageId( $data['initialLanguage'] ) );
                $out = $this->restCall( $method, $url, $payload );
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
                $method = 'PUT';
                $url = "/content/locations/remote/$RemoteNodeRemoteID";
                $payload = array(
                    'mainLocationRemoteId' => $RemoteNodeRemoteID
                );
                $out = $this->restCall( $method, $url, $payload );
                if ( !is_array( $out ) || !isset( $out['mainLocationId'] ) || !isset( $out['Id'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                if ( $out['mainLocationId'] != $out['Id'] )
                {
                    throw new Exception( "mainLocationId in response does not match what was sent", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                return 0;

            case eZContentStagingEvent::ACTION_UPDATEOBJECSTATE:
                $method = 'PUT';
                $RemoteObjRemoteID = $this->buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                $url = "/content/objects/remote/$RemoteObjRemoteID/states";
                $payload = $data['stateList'];
                $this->restCall( $method, $url, $payload );
                return 0;

            case eZContentStagingEvent::ACTION_UPDATEPRIORITY:
                $method = 'PUT';
                foreach ( $data['priorities'] as $priority )
                {
                    $RemoteNodeRemoteID = $this->buildRemoteId( $priority['nodeID'], $priority['nodeRemoteID'] );
                    $url = "/content/locations/remote/$RemoteNodeRemoteID";
                    $payload = array(
                        'priority' => $priority['priority']
                    );
                    $out = $this->restCall( $method, $url, $payload );
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

            case eZContentStagingEvent::ACTION_INITIALIZEFEED:
                // set remote id on remote node and remote object
                $RemoteNodeRemoteID = $this->buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );

                $method = 'GET';
                /// @todo switch from rest api v1 (content/node) to v2 (content/location)
                $url = "/content/locations/{$data['remoteNodeID']}";
                //$url = "/content/locations/{$data['remoteNodeID']}";
                $out = $this->restCall( $method, $url );
                if ( !is_array( $out ) || !isset( $out['contentId'] ) )
                {
                    throw new Exception( "Received invalid data in response", eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR );
                }
                $remoteObjID = $out['contentId'];

                $method = 'PUT';
                $url = "/content/locations/{$data['remoteNodeID']}";
                $payload = array(
                    'remoteId' => $RemoteNodeRemoteID
                );
                $out = $this->restCall( $method, $url, $payload );
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
                $method = 'PUT';
                $url = "/content/objects/$remoteObjID";
                $payload = array(
                    'remoteId' => $RemoteObjRemoteID
                );
                $out = $this->restCall( $method, $url, $payload );
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

        //eZDebug::writeDebug( "Cheking node: " . $node->attribute( 'node_id' ), __METHOD__ );
//echo "Cheking node: " . $node->attribute( 'node_id' ) . "\n";

        $nodeId = $node->attribute( 'node_id' );
        $RemoteNodeRemoteID = $this->buildRemoteId( $node->attribute( 'node_id' ), $node->attribute( 'remote_id' ) );
        $method = 'GET';
        $url = "/content/locations/remote/{$RemoteNodeRemoteID}";
        try
        {
            $remote = $this->restCall( $method, $url );
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
                    $parentRemoteNodeRemoteID = $this->buildRemoteId( $parent->attribute( 'node_id' ), $parent->attribute( 'remote_id' ) );
                    $method = 'GET';
                    $url = "/content/locations/remote/$parentRemoteNodeRemoteID";
                    try
                    {
                        $remoteParent = $this->restCall( $method, $url );
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
                    catch( exception $e )
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
        $RemoteObjectRemoteID = $this->buildRemoteId( $object->attribute( 'id' ), $object->attribute( 'remote_id' ), 'object' );
        $method = 'GET';
        $url = "/content/objects/remote/{$RemoteObjectRemoteID}";
        try
        {
            $remote = $this->restCall( $method, $url );
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
    * @return mixed
    * @todo implement result checking and error code parsing ...
    */
    protected function restCall( $method, $url, $payload=array() )
    {
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
    protected function encodeObject( $objectID, $versionNr, $locale, $isupdate=false, $RemoteObjRemoteID=false, $syncdate=false )
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
        foreach( $version->contentObjectAttributes( $locale ) as $attribute )
        {
            if ( !$attribute->attribute( 'has_content' ) )
            {
                continue;
            }

            $name = $attribute->attribute( 'contentclass_attribute_identifier' );
            $out['fields'][$name] = (array) new eZContentStagingField( $attribute, $locale, $ridGenerator );
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

    /**
     * @todo !important this function should possibly be calling a handler for greater flexibility
     */
    protected function getRemoteIdGenerator()
    {
        $ini = eZINI::instance( 'contentsatging.ini' );
        $targetId = $this->target->attribute( 'id' );
        if ( $ini->hasVariable( "Target_" . $targetId,  'RemoteIdGeneratorClass' ) )
        {
            $class = $ini->variable( "Target_" . $targetId,  'RemoteIdGeneratorClass' );
        }
        else
        {
            $class = 'eZContentStagingLocalAsRemoteIdGenerator';
        }
        if ( !class_exists( $class ) )
        {
            eZDebug::writeError( "Cannot generate remote id for object/node for target feed $feedId: class $class not found", __METHOD__ );
            return null;
        }
        return new $class( $targetId );
    }
}

?>