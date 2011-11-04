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

class ezRestApiGGWSClientStagingTransport implements eZContentStagingTransport
{

    function __construct( eZContentStagingTarget $target )
    {
        $this->target = $target;
    }

    function syncEvents( array $events )
    {
        $results = array();
        foreach( $events as $event )
        {
            $data = $event->getData();
            switch( $event->attribute( 'to_sync' ) )
            {
                case eZContentStagingEvent::ACTION_ADDLOCATION:
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    $RemoteParentNodeRemoteID = self::buildRemoteId( $data['parentNodeID'] , $data['parentNodeRemoteID'] );
                    /// @todo !important test that $RemoteObjRemoteID is not null
                    $method = 'PUT';
                    $url = "/content/objects/remote/$RemoteObjRemoteID/locations?parentRemoteId=$RemoteParentNodeRemoteID";
                    $payload = array(
                        'remoteId' => $RemoteNodeRemoteID,
                        'priority' => $data['priority'],
                        'sortField' => self::encodeSortField( $data['sortField'] ),
                        'sortOrder' => self::encodeSortOrder( $data['sortOrder'] )
                        );
                    $out = $this->restCall( $method, $url, $payload );
                    break;

                case eZContentStagingEvent::ACTION_DELETE:
                    $method = 'DELETE';
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $url = "/content/objects/remote/$RemoteObjRemoteID?trash={$data['trash']}";
                    $out = $this->restCall( $method, $url );
                    break;

                case eZContentStagingEvent::ACTION_HIDEUNHIDE:
                    $method = 'POST';
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    $url = "/content/locations/remote/$RemoteNodeRemoteID&hide=" . $data['hide'];
                    //$payload = array(
                    //    'hide' => $data['hide'],
                    //    );
                    $out = $this->restCall( $method, $url );
                    break;

                case eZContentStagingEvent::ACTION_MOVE:
                    $method = 'PUT';
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    $RemoteParentNodeRemoteID = self::buildRemoteId( $data['parentNodeID'], $data['parentNodeRemoteID'] );
                    $url = "/content/locations/remote/$RemoteNodeRemoteID/parent?destParentRemoteId=$RemoteParentNodeRemoteID";
                    $out = $this->restCall( $method, $url );
                    break;

                case eZContentStagingEvent::ACTION_PUBLISH:
                    // this can be either a content creation or update
                    /// @todo what if we create many drafts and we discard them? Is the first version created still 1? test it!
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    if ( $data['version'] == 1 )
                    {
                        $method = 'POST';
                        $RemoteParentNodeRemoteID = self::buildRemoteId( $data['parentNodeID'], $data['parentNodeRemoteID'] );
                        $url = "/content/objects?parentRemoteId=$RemoteParentNodeRemoteID";
                        $payload = self::encodeObject( $event->attribute( 'object_id' ),  $data['version'], $data['locale'], false, $RemoteObjRemoteID );
                    }
                    else
                    {
                        $method = 'PUT';
                        $url = "/content/objects/remote/$RemoteObjRemoteID";
                        $payload = self::encodeObject( $event->attribute( 'object_id' ),  $data['version'], $data['locale'], true );
                    }

                    if ( $payload )
                    {
                        $out = $this->restCall( $method, $url, $payload );
                    }
                    else
                    {
                        $out = eZContentStagingEvent::ERROR_OBJECTCANNOTSERIALIZE;
                    }
                    break;

                case eZContentStagingEvent::ACTION_REMOVELOCATION:
                    $method = 'DELETE';
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    $url = "/content/locations/remote/$RemoteNodeRemoteID&trash=" . self::encodeTrash( $data['trash'] );
                    $out = $this->restCall( $method, $url );
                    break;

                case eZContentStagingEvent::ACTION_REMOVETRANSLATION:
                    $method = 'DELETE';
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $baseurl = "/content/objects/remote/$RemoteObjRemoteID/translations/";
                    foreach ( $data['translations'] as $translation )
                    {
                        $url = $baseurl . self::encodeLanguageId( $translation );
                        $out = $this->restCall( $method, $url );
                        if ( $out != 0 )
                        {
                            /// @todo shall we break here or what? we only updated a few priorities, not all of them...
                            break;
                        }
                    }
                    break;

                case eZContentStagingEvent::ACTION_UPDATESECTION:
                    $method = 'PUT';
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $url = "/content/objects/remote/$RemoteObjRemoteID/section?sectionId={$data['sectionID']}";
                    $out = $this->restCall( $method, $url );
                    break;

                case eZContentStagingEvent::ACTION_SORT:
                    $method = 'PUT';
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    $url = "/content/locations/remote/$RemoteNodeRemoteID";
                    $payload = array(
                        // @todo can we omit safely to send priority?
                        //'priority' => $data['priority'],
                        'sortField' => self::encodeSortField( $data['sortField'] ),
                        'sortOrder' => self::encodeSortOrder( $data['sortOrder'] )
                        );
                    $out = $this->restCall( $method, $url, $payload );
                    break;

                case 'swap':
                    /// @todo ...
                    $out = -333;
                    break;

                case eZContentStagingEvent::ACTION_UPDATEALWAYSAVAILABLE:
                    $method = 'PUT';
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $baseurl = "/content/objects/remote/$RemoteObjRemoteID";
                    $payload = array( 'alwaysAvailable' => self::encodeAlwaysAvailable( $data['alwaysAvailable'] ) );
                    $out = $this->restCall( $method, $url, $payload );
                    break;

                case eZContentStagingEvent::ACTION_UPDATEINITIALLANGUAGE:
                    $method = 'PUT';
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $baseurl = "/content/objects/remote/$RemoteObjRemoteID";
                    $payload = array( 'initialLanguage' => self::encodeLanguageId( $data['initialLanguage'] ) );
                    $out = $this->restCall( $method, $url, $payload );
                    break;

                case eZContentStagingEvent::ACTION_UPDATEMAINASSIGNMENT:
                    // not supported yet, as we have to figure out how to translate this into a set of REST API calls
                    $out = -666;
                    break;

                case eZContentStagingEvent::ACTION_UPDATEPRIORITY:
                    $method = 'PUT';
                    foreach ( $data['priorities'] as $priority )
                    {
                        $RemoteNodeRemoteID = self::buildRemoteId( $priority['nodeID'], $priority['nodeRemoteID'] );
                        $url = "/content/locations/remote/$RemoteNodeRemoteID";
                        $payload = array(
                            'priority' => $priority['priority']
                        );
                        $out = $this->restCall( $method, $url, $payload );
                        if ( $out != 0 )
                        {
                            /// @todo shall we break here or what? we only updated a few priorities, not all of them...
                            break;
                        }
                    }
                    break;

                case eZContentStagingEvent::ACTION_INITIALIZEFEED:
                    // set remote id on remote node and remote object
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );

                    $method = 'GET';
                    /// @todo switch from rest api v1 (content/node) to v2 (content/location)
                    $url = "/content/locations/{$data['remoteNodeID']}";
                    //$url = "/content/locations/{$data['remoteNodeID']}";
                    $out = $this->restCall( $method, $url );
                    if ( !is_array( $out ) )
                    {
                       break;
                    }
                    if ( !isset( $out['contentId'] ) )
                    {
                        /// @todo !important use a specific error code
                        $out = eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR;
                        break;
                    }
                    $remoteObjID = $out['contentId'];

                    $method = 'PUT';
                    $url = "/content/locations/{$data['remoteNodeID']}";
                    $payload = array(
                        'remoteId' => $RemoteNodeRemoteID
                    );
                    $out = $this->restCall( $method, $url, $payload );
                    if ( $out != 0 )
                    {
                        break;
                    }

                    // nb: this is a non-transactional API: we might succeed in updating
                    // node remote id but not object remote id. In such case
                    // we do NOT rollback our changes
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $method = 'PUT';
                    $url = "/content/objects/$remoteObjID";
                    $payload = array(
                        'remoteId' => $RemoteObjRemoteID
                    );
                    $out = $this->restCall( $method, $url, $payload );

                    break;

                default:
                    $out = eZContentStagingEvent::ERROR_EVENTTYPEUNKNOWNTOTRANSPORT; // should we store this code in this class instead?
            }
            $results[] = $out;
        }

        return $results;
    }

    /// @todo implement result checking and error code parsing ...
    protected function restCall( $method, $url, $payload=array() )
    {
        $options = array( 'method' => $method, 'requestType' => 'application/json' );

        /// @todo test that ggws is enabled and that there is a server defined
        $results = ggeZWebservicesClient::send( $this->target->attribute( 'server' ), $url, $payload, true, $options );
        if ( !isset( $results['result'] ) )
        {
            /// @todo best would be to decode $results['error'], which we get as a string, instead of using a generic error code
            return eZContentStagingEvent::ERROR_GENERICTRANSPORTERROR;
        }
        $response = $results['result'];
        if ( $response->isFault() )
        {
            // currently we have error ranges -101 to -30x here
            return $response->faultCode();
        }

        return $response->value();
    }

    /**
    * Encodes an object's version (single language) to be sent to the remote server
    *
    * @todo move to an external class, to avoid parsing all this code for every event?
    */
    protected function encodeObject( $objectID, $versionNr, $locale, $isupdate=false, $RemoteObjRemoteID=false )
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

        foreach( $version->contentObjectAttributes( $locale ) as $attribute )
        {
            if ( !$attribute->attribute( 'has_content' ) )
            {
                continue;
            }

            $name = $attribute->attribute( 'contentclass_attribute_identifier' );
            $datatype = $attribute->attribute( 'data_type_string' );

            /// @see serializeContentObjectAttribute and toString in different datatypes
            ///      for datatypes that need special treatment

            /// @todo implement this conversion within the datatypes themselves:
            /// it is a much better idea...
            switch( $datatype )
            {

                case 'ezobjectrelation':
                    $relatedObjectID = $attribute->attribute( 'content' );
                    $relatedObject = eZContentObject::fetch( $relatedObjectID );
                    if ( $relatedObject )
                    {
                        $value = array( 'remoteId' => self::buildRemoteId( $relatedObjectID, $relatedObject->attribute( 'remote_id' ), 'object' ) );
                    }
                    else
                    {
                        eZDebug::writeError( "Cannot encode attribute of object $objectID for push to staging server: version $versionNr - related object $relatedObjectID not found for attribute in lang $locale for field $name", __METHOD__ );
                        continue;
                    }
                    break;

                case 'ezobjectrelationlist':
                    $relation_list = $attribute->attribute( 'content' );
                    $relation_list = $relation_list['relation_list'];
                    $value = array();
                    foreach ( $relation_list as $relatedObjectInfo )
                    {
                        // nb: for the object relation we check for objects that have disappeared we do it here too. Even though it is bad for perfs...
                        $relatedObject = eZContentObject::fetch( $relatedObjectInfo['contentobject_id'] );
                        if ( $relatedObject )
                        {
                            eZDebug::writeError( "Cannot encode attribute of object $objectID for push to staging server: version $versionNr - related object {$relatedObjectInfo['contentobject_id']} not found for attribute in lang $locale for field $name", __METHOD__ );
                            continue;
                        }
                        $value = array( 'remoteId' => self::buildRemoteId( $relatedObjectInfo['contentobject_id'], $relatedObjectInfo['contentobject_remote_id'], 'object' ) );
                    }
                    break;

                /// @todo shall we check for datatype->isRegularFileInsertionSupported() instead of hardcoding here known datatypes?
                /*case 'ezimage':
                case 'ezbinaryfile':
                case 'ezmedia':
                    /// is this check redundant with the above has_content?
                    if ( !$attribute->hasStoredFileInformation( $bject, $version, $locale ) )
                    {
                        continue;
                    }
                    $fileInfo = $attribute->storedFileInformation( $bject, $version, $locale );
                    if ( !$fileInfo )
                    {
                        eZDebug::writeError( "Cannot encode attribute of object $objectID for push to staging server: version $versionNr - binary not found for attribute in lang $locale for field $name", __METHOD__ );
                        continue;
                    }

                    $fileName = $fileInfo['filepath'];
                    $file = eZClusterFileHandler::instance( $fileName );
                    if ( ! $file->exists() )
                    {
                        eZDebug::writeError( "Cannot encode file for object $objectID for push to staging server: version $versionNr - binary not found for attribute in lang $locale for field $name", __METHOD__ );
                        continue;
                    }
                    /// @todo for big files, we should do piecewise base64 encoding, or we go over memory limit
                    $value = base64_encode( $file->fetchContents() );*/

                // nb: this datatype has, as of eZ 4.5, a broken toString method
                case 'ezmedia':
                    $content = $attribute->attribute( 'content' );
                    $file = eZClusterFileHandler::instance( $content->attribute( 'filepath' ) );
                    /// @todo for big files, we should do piecewise base64 encoding, or we go over memory limit
                    $value = array(
                        'fileSize' => (int)$content->attribute( 'filesize' ),
                        'fileName' => $content->attribute( 'original_filename' ),
                        'width' => $content->attribute( 'width' ),
                        'height' => $content->attribute( 'height' ),
                        'hasController' => (bool)$content->attribute( 'has_controller' ),
                        'controls' => (bool)$content->attribute( 'controls' ),
                        'isAutoplay' => (bool)$content->attribute( 'is_autoplay' ),
                        'pluginsPage' => $content->attribute( 'pluginspage' ),
                        'quality' => $content->attribute( 'quality' ),
                        'isLoop' => (bool)$content->attribute( 'is_loop' ),
                        'content' => base64_encode( $file->fetchContents() )
                        );
                    break;

                case 'ezbinaryfile':
                    $content = $attribute->attribute( 'content' );
                    $file = eZClusterFileHandler::instance( $content->attribute( 'filepath' ) );
                    /// @todo for big files, we should do piecewise base64 encoding, or we go over memory limit
                    $value = array(
                        'fileSize' => (int)$content->attribute( 'filesize' ),
                        'fileName' => $content->attribute( 'original_filename' ),
                        'content' => base64_encode( $file->fetchContents() )
                        );
                    break;

                case 'ezimage':
                    $content = $attribute->attribute( 'content' );
                    $original = $content->attribute( 'original' );
                    $file = eZClusterFileHandler::instance( $original['url'] );
                    /// @todo for big files, we should do piecewise base64 encoding, or we go over memory limit
                    $value = array(
                        'fileSize' => (int)$original['filesize'],
                        'fileName' => $original['original_filename'],
                        'alternativeText' => $original['alternative_text'],
                        'content' => base64_encode( $file->fetchContents() )
                        );
                    break;

                // known bug in ezuser serialization: #018609
                case 'ezuser':

                // see also http://issues.ez.no/IssueList.php?Search=fromstring&SearchIn=1
                // see also http://issues.ez.no/IssueList.php?Search=tostring&SearchIn=1
                default:
                    $value = $attribute->toString();
            }

            $out['fields'][$name] = array(
                'fieldDef' => $datatype,
                'value' => $value,
                'language' => $locale );
        }

        if ( !$isupdate )
        {
            $out['initialLanguage'] = $object->attribute( 'initial_language_code' );
            $out['alwaysAvailable'] = $object->attribute( 'always_available' );
            $out['remoteId'] = $RemoteObjRemoteID;
            $out['sectionId'] = $object->attribute( 'section_id' );
            $out['ownerId'] = $object->attribute( 'owner_id' );
        }

        return $out;
    }

    /// @todo finish
    static protected function encodeSortField( $value )
    {
            /*return "PATHSTRING";
            return "CREATED";
            return "SECTIONIDENTIFIER";
            return "FIELD";*/
        $fields = array(
            eZContentObjectTreeNode::SORT_FIELD_PATH => "PATH",
            eZContentObjectTreeNode::SORT_FIELD_PUBLISHED => 2,
            eZContentObjectTreeNode::SORT_FIELD_MODIFIED => "MODIFIED",
            eZContentObjectTreeNode::SORT_FIELD_SECTION => "SECTIONID",
            eZContentObjectTreeNode::SORT_FIELD_DEPTH => 5,
            eZContentObjectTreeNode::SORT_FIELD_CLASS_IDENTIFIER => 6,
            eZContentObjectTreeNode::SORT_FIELD_CLASS_NAME => 7,
            eZContentObjectTreeNode::SORT_FIELD_PRIORITY => "PRIORITY",
            eZContentObjectTreeNode::SORT_FIELD_NAME => "NAME",
            eZContentObjectTreeNode::SORT_FIELD_MODIFIED_SUBNODE => 10,
            eZContentObjectTreeNode::SORT_FIELD_NODE_ID => 11,
            eZContentObjectTreeNode::SORT_FIELD_CONTENTOBJECT_ID => 12
        );
        return $fields[$value];
    }

    static protected function encodeSortOrder( $value )
    {
        return $value ? "ASC" : "DESC";
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
    * @todo this function should possibly be calling a handler for greater flexibility
    */
    static protected function buildRemoteId( $sourceId, $sourceRemoteId, $type='node' )
    {
        return $sourceRemoteId;
    }
}

?>
