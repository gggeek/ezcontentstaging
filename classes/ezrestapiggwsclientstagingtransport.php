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
                    $url = "/content/locations?remoteId=$RemoteNodeRemoteID&hide=" . $data['hide'];
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
                    $url = "/content/locations?remoteId=$RemoteNodeRemoteID&trash=" . self::encodeTrash( $data['trash'] );
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
                    $url = "/content/locations?remoteId=$RemoteNodeRemoteID";
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
                        $url = "/content/locations?remoteId=$RemoteNodeRemoteID";
                        $payload = array(
                            // @todo can we omit safely to send rest of data?
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
        $results = ggeZWebservicesClient::call( $this->target->attribute( 'server' ), $url, $payload, $options );
        if ( !isset( $results['result'] ) )
        {
            /// @todo settle on an error code. Best would be to decode $results['error'], which we get as a string
            return -999;
        }
        return $results['result'];
    }

    /**
    * Encodes an object's version (single language) to be sent to the remote server
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
            switch( $datatype )
            {
                /// @see ezbinaryfilehandler for an example of fething binary data from attributes

                /// @todo shall we check for datatype->isRegularFileInsertionSupported() instead of hardcoding here known datatypes?
                case 'ezimage':
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
                        eZDebug::writeError( "Cannot encode object $objectID for push to staging server: version $versionNr - binary not found for attribute in lang $locale for field $name", __METHOD__ );
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
                    $value = base64_encode( $file->fetchContents() );
                    break;

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
