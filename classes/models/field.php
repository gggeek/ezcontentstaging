<?php
/**
 * The contentStagingField class is used to provide the representation of a
 * Content Field used in REST api calls.
 *
 * It mainly takes care of exposing the needed attributes and casting each of
 * them in the correct type.
 *
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZContentStagingField
{

    public $fieldDef;
    public $value;
    public $language;

    /**
    * The constructor is where most of the magic happens.
    * It is called for encoding fields.
    * NB: if passed a $ridGenerator, all local obj/node ids are substituted with remote ones, otherwise not
    *
    * @param eZContentStagingRemoteIdGenerator $ridGenerator (or null)
    * @see serializeContentObjectAttribute and toString in different datatypes
    *      for datatypes that need special treatment
    * @see http://issues.ez.no/IssueList.php?Search=tostring&SearchIn=1
    * @todo implement this conversion within the datatypes themselves:
    *       it is a much better idea... (check datatypes that support a fromHash method, use it)
    */
    function __construct( eZContentObjectAttribute $attribute, $locale, $ridGenerator )
    {
        $this->fieldDef = $attribute->attribute( 'data_type_string' );
        $this->language = $locale;

        switch( $this->fieldDef )
        {
            case 'ezauthor':
                $ezauthor = $attribute->attribute( 'content' );
                $authors = array();
                foreach( $ezauthor->attribute( 'author_list' ) as $author )
                {
                    $authors[] = array(
                        'name' => $author['name'],
                        'email' => $author['email']
                    );
                }
                $this->value = $authors;
                break;

                // serialized as a struct
            case 'ezbinaryfile':
                $content = $attribute->attribute( 'content' );
                $file = eZClusterFileHandler::instance( $content->attribute( 'filepath' ) );
                /// @todo for big files, we should do piecewise base64 encoding, or we might go over memory limit
                $this->value = array(
                    'fileSize' => (int)$content->attribute( 'filesize' ),
                    'fileName' => $content->attribute( 'original_filename' ),
                    'content' => base64_encode( $file->fetchContents() )
                    );
                break;

            case 'ezboolean':
                $this->value = (bool) $attribute->toString();
                break;

            /// @todo shall we use iso 8601 format for dates?
            case 'ezdate':
            case 'ezdatetime':
                $this->value = (int) $attribute->toString();
                break;

            /// @todo serialize with wanted precision, using json native float type
            //case 'ezfloat':
            //    $this->value = ...;
            //    break;

            case 'ezgmaplocation':
                /// @todo shall we double check that data is valid, ie attribute( 'data_int' ) == 1 ?
                $gmaplocation = $attribute->attribute( 'content' );
                $this->value = array(
                    "latitude" => $gmaplocation->attribute( 'latitude' ),
                    "longitude" =>  $gmaplocation->attribute( 'longitude' ),
                    "address" =>  $gmaplocation->attribute( 'address' )
                );
                break;

            // serialized as a struct
            case 'ezimage':
                $content = $attribute->attribute( 'content' );
                $original = $content->attribute( 'original' );
                $file = eZClusterFileHandler::instance( $original['url'] );
                /// @todo for big files, we should do piecewise base64 encoding, or we might go over memory limit
                $this->value = array(
                    'fileSize' => (int)$original['filesize'],
                    'fileName' => $original['original_filename'],
                    'alternativeText' => $original['alternative_text'],
                    'content' => base64_encode( $file->fetchContents() )
                    );
                break;

            case 'ezinteger':
                $this->value = (int) $attribute->toString();
                break;

            // serialize as structured array instead of string:
            case 'ezkeyword':
                $keyword = new eZKeyword();
                $keyword->fetch( $attribute );
                $this->value = $keyword->attribute( 'keywords' );
                break;

                // serialized as a struct
                // nb: this datatype has, as of eZ 4.5, a broken toString method
            case 'ezmedia':
                $content = $attribute->attribute( 'content' );
                $file = eZClusterFileHandler::instance( $content->attribute( 'filepath' ) );
                /// @todo for big files, we should do piecewise base64 encoding, or we go over memory limit
                $this->value = array(
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

            // serialized as a single string of either local or remote id
            case 'ezobjectrelation':
                // slightly more intelligent than base "toString" method: we always check for presence of related object
                $relatedObjectID = $attribute->attribute( 'data_int' );
                $relatedObject = eZContentObject::fetch( $relatedObjectID );
                if ( $relatedObject )
                {
                    if ( $ridGenerator )
                    {
                        $this->value = array( 'remoteId' => $ridGenerator->buildRemoteId( $relatedObjectID, $relatedObject->attribute( 'remote_id' ), 'object' ) );
                    }
                    else
                    {
                        $this->value = $relatedObjectID;
                    }
                }
                else
                {
                    eZDebug::writeError( "Cannot encode attribute - related object $relatedObjectID not found for attribute in lang $locale", __METHOD__ );
                    $this->value = null;
                }
                break;

            // serialized as an array of local/remote ids
            case 'ezobjectrelationlist':
                if ( $ridGenerator )
                {
                    $relation_list = $attribute->attribute( 'content' );
                    $relation_list = $relation_list['relation_list'];
                    $values = array();
                    foreach ( $relation_list as $relatedObjectInfo )
                    {
                        // nb: for the object relation we check for objects that have disappeared we do it here too. Even though it is bad for perfs...
                        $relatedObject = eZContentObject::fetch( $relatedObjectInfo['contentobject_id'] );
                        if ( !$relatedObject )
                        {
                            eZDebug::writeError( "Cannot encode attribute for push to staging server: related object {$relatedObjectInfo['contentobject_id']} not found for attribute in lang $locale", __METHOD__ );
                            continue;
                        }
                        $values[] = array( 'remoteId' => $ridGenerator->buildRemoteId( $relatedObjectInfo['contentobject_id'], $relatedObjectInfo['contentobject_remote_id'], 'object' ) );
                    }
                    $this->value = $values;
                }
                else
                {
                    $this->value = explode( '-', $attribute->toString() );
                }
                break;

            case 'ezselection':
                $this->value = explode( '|', $attribute->toString() );
                break;

            case 'ezsrrating':
                $this->value = array( 'can_rate' => (bool)$attribute->attribute( 'data_int' ) );
                break;

            case 'ezuser':
                $userID = $attribute->attribute( "contentobject_id" );
                if ( empty( $GLOBALS['eZUserObject_' . $userID] ) )
                {
                    $user = eZUser::fetch( $userID );
                    if ( $user )
                    {
                        $GLOBALS['eZUserObject_' . $userID] = eZUser::fetch( $userID );
                    }
                }
                else
                {
                    $user = $GLOBALS['eZUserObject_' . $userID];
                }

                if ( $user && $user->attribute( 'login' ) != '' )
                {
                    $this->value = array(
                        'login' => $user->attribute( 'login' ),
                        'email' => $user->attribute( 'email' ),
                        'password_hash' => $user->attribute( 'password_hash' ),
                        'password_hash_type' => eZUser::passwordHashTypeName( $user->attribute( 'password_hash_type' ) ),
                        'is_enabled' => (bool)$user->isEnabled()
                    );
                }
                else
                {
                    $this->value = null;
                }
                break;

            case 'ezxmltext':
                if ( $ridGenerator )
                {
                    // code taken from eZXMLTextType::serializeContentObjectAttribute
                    $xmlString = $attribute->attribute( 'data_text' );
                    $doc = new DOMDocument( '1.0', 'utf-8' );
                    /// @todo !important suppress errors in the loadXML call?
                    if ( $xmlString != '' && $doc->loadXML( $xmlString ) )
                    {
                        /** For all links found in the XML, do the following:
                        * - add "href" attribute fetching it from ezurl table.
                        * - remove "id" attribute.
                        * For embeds, objects, embeds-inline, replace id with remote_id
                        * @see eZXMLTextType::serializeContentObjectAttribute
                        */
                        $links = $doc->getElementsByTagName( 'link' );
                        $embeds = $doc->getElementsByTagName( 'embed' );
                        $objects = $doc->getElementsByTagName( 'object' );
                        $embedsInline = $doc->getElementsByTagName( 'embed-inline' );

                        self::transformLinksToRemoteLinks( $links, $ridGenerator );
                        self::transformLinksToRemoteLinks( $embeds, $ridGenerator );
                        self::transformLinksToRemoteLinks( $objects, $ridGenerator );
                        self::transformLinksToRemoteLinks( $embedsInline, $ridGenerator );

                        /*$DOMNode = $datatype->createContentObjectAttributeDOMNode( $attribute );
                        $importedRootNode = $DOMNode->ownerDocument->importNode( $doc->documentElement, true );
                        $DOMNode->appendChild( $importedRootNode );*/
                    }
                    else
                    {
                        /// @todo log a warning
                        $parser = new eZXMLInputParser();
                        $doc = $parser->createRootNode();
                        //$xmlText = eZXMLTextType::domString( $doc );
                    }
                    $this->value = $doc->saveXML();
                }
                else
                {
                    $this->value = $attribute->toString();
                }
                break;

            case 'ezurl':
                $this->value = array(
                    'url' => eZURL::url( $attribute->attribute( 'data_int' ) ),
                    'text' => $attribute->attribute( 'data_text' ) );
                break;

            // known bug in ezuser serialization: #018609
            case 'ezuser':

            default:
                $this->value = $attribute->toString();
        }
    }

    /**
    * NB: we assume that someone else has checked for proper type matching between attr. and value
    * NB: we assume that attributes are not empty here - we leave the test for .has_content to the caller
    *
    * @todo implement all missing validation that does not happen when we go via fromString...
    * @todo decide: shall we throw an exception if data does not validate or just emit a warning?
    * @todo check datatypes that support a fromHash method, use it instead of hard-coded conversion here
    *
    * @see eZDataType::unserializeContentObjectAttribute
    * @see eZDataType::fromstring
    * @see http://issues.ez.no/IssueList.php?Search=fromstring
    */
    static function decodeValue( $attribute, $value )
    {
        $type = $attribute->attribute( 'data_type_string' );
        switch( $type )
        {
            case 'ezauthor':
                $author = new eZAuthor( );
                foreach ( $value as $authorData )
                {
                    $author->addAuthor( -1, $authorData['name'], $authorData['email'] );
                }
                $attribute->setContent( $author );
                break;

            case 'ezbinaryfile':
            case 'ezmedia':
            case 'ezimage':
                if ( !is_array( $value ) || !isset( $value['fileName'] ) || !isset( $value['content'] ) )
                {
                    $attrname = $attribute->attribute( 'contentclass_attribute_identifier' );
                    eZDebug::writeWarning( "Can not create binary file because fileName or content is missing in attribute $attrname", __METHOD__ );
                    $ok = false;
                    break;
                }

                $tmpDir = eZINI::instance()->variable( 'FileSettings', 'TemporaryDir' ) . '/' . uniqid() . '-' . microtime( true );
                $fileName = $value['fileName'];
                /// @todo test if base64 decoding fails and if decoded img filesize is ok
                eZFile::create( $fileName, $tmpDir, base64_decode( $value['content'] ) );

                $path = "$tmpDir/$fileName";
                if ( $type == 'image' )
                {
                    $path .= "|{$value['alternativeText']}";
                }
                $ok = $attribute->fromString( $path );

                if ( $ok && $type == 'ezmedia' )
                {
                    $mediaFile = $attribute->attribute( 'content' );
                    $mediaFile->setAttribute( 'width', $value['width'] );
                    $mediaFile->setAttribute( 'height', $value['height'] );
                    $mediaFile->setAttribute( 'has_controller', $value['hasController'] );
                    $mediaFile->setAttribute( 'controls', $value['controls'] );
                    $mediaFile->setAttribute( 'is_autoplay', $value['isAutoplay'] );
                    $mediaFile->setAttribute( 'pluginspage', $value['pluginsPage'] );
                    $mediaFile->setAttribute( 'quality', $value['quality'] );
                    $mediaFile->setAttribute( 'is_loop', $value['isLoop'] );
                    $mediaFile->store();
                }

                eZDir::recursiveDelete( $tmpDir, false );
                break;

            // serialized as array instead of single string
            case 'ezgmaplocation':
                $location = new eZGmapLocation( array(
                    'contentobject_attribute_id' => $attribute->attribute( 'id' ),
                    'contentobject_version' => $attribute->attribute( 'version' ),
                    'latitude' => $value['latitude'],
                    'longitude' => $value['longitude'],
                    'address' => $value['address']
                ) );
                $attribute->setContent( $location );
                $attribute->setAttribute( 'data_int', 1 );
                break;

            case 'ezkeyword':
                $attribute->fromString( implode( ',', $value ) );
                break;

            case 'ezobjectrelation':
                if ( is_array( $value ) && isset( $value['remoteId'] ) )
                {
                    $object = eZContentObject::fetchByRemoteId( $value['remoteId'] );
                    if ( $object )
                    {
                        // avoid going via fromstring for a small speed gain
                        $attribute->setAttribute( 'data_int', $object->attribute( 'id' ) );
                        $ok = true;
                    }
                    else
                    {
                        $attrname = $attribute->attribute( 'contentclass_attribute_identifier' );
                        eZDebug::writeWarning( "Can not create relation because object with remote id {$value['remoteId']} is missing in attribute $attrname", __METHOD__ );
                        $ok = false;
                    }
                }
                else
                {
                    $ok = $attribute->fromString( $value );
                }
                break;

            case 'ezobjectrelationlist':
                $localIds = array();
                foreach( $value as $key => $item )
                {
                    if ( is_array( $item ) && isset( $item['remoteId'] ) )
                    {
                        $object = eZContentObject::fetchByRemoteId( $item['remoteId'] );
                        if ( $object )
                        {
                            $localIds[] = $object->attribute( 'id' );
                        }
                        else
                        {
                            $attrname = $attribute->attribute( 'contentclass_attribute_identifier' );
                            eZDebug::writeWarning( "Can not create relation because object with remote id {$item['remoteId']} is missing in attribute $attrname", __METHOD__ );
                        }
                    }
                    else
                    {
                        $localIds[] = $item;
                    }
                }
                /// @todo we only catch one error type here, but we should catch more
                if ( count( $localIds ) == 0 && count( $value ) > 0 )
                {
                    $ok = false;
                }
                else
                {
                    $ok = $attribute->fromString( implode( '-', $localIds ) );
                }
                break;

            case 'ezselection':
                $attribute->fromString( implode( '|', $value ) );
                break;

            case 'ezsrrating':
                $attribute>setAttribute( 'data_int', $value['can_rate'] );
                break;


            case 'ezurl':
                $urlID = eZURL::registerURL( $value['url'] );
                $attribute->setAttribute( 'data_int', $urlID );
                if( isset( $value['text'] ) )
                {
                    $attribute->setAttribute( 'data_text', $value['text'] );
                }
                break;

            case 'ezuser':
                // convoluted logic: creation or update of user account
                if ( is_array( $value ) )
                {
                    $login = @$value['login'];
                    $email = @$value['email'];
                    $user = null;

                    // we allow to identify a user by login, or by email if eZUser::requireUniqueEmail is true
                    if ( $login == '' && ( $email == '' || !eZUser::requireUniqueEmail() ) )
                    {
                        // cannot identify nor create
                        break;
                    }

                    if ( $login != '' )
                    {
                        $user = eZUser::fetchByName( $login );
                    }
                    if ( $user == null && $email != '' && eZUser::requireUniqueEmail() )
                    {
                        $user = eZUser::fetchByEmail( $email );
                    }
                    if ( $user == null && $login != '' && $email != '' )
                    {
                        $user = eZUser::create( $attribute->attribute( 'contentobject_id' ) );
                    }

                    if ( $user == null )
                    {
                        break;
                    }

                    /// @todo what if we try to update email attribute making it a double,
                    ///       not respecting requireUniqueEmail?

                    if ( $login != '' ) $user->setAttribute( 'login', $login );
                    if ( $email != '' ) $user->setAttribute( 'email', $email );
                    if ( isset( $value['password_hash'] ) )
                    {
                        $user->setAttribute( 'password_hash', $value['password_hash'] );
                    }
                    if ( isset( $value['password_hash_type'] ) )
                    {
                        $user->setAttribute( 'password_hash_type', eZUser::passwordHashTypeID( $value['password_hash_type'] ) );
                    }
                    if( isset( $value['is_enabled'] ) )
                    {
                        $userSetting = eZUserSetting::fetch(
                            $attribute->attribute( 'contentobject_id' )
                        );
                        $userSetting->setAttribute( "is_enabled", (int)$value['is_enabled'] );
                        $userSetting->store();
                    }
                    $user->store();
                }
                break;

            /// @see eZXMLTextType::unserializeContentObjectAttribute
            case 'ezxmltext':
                $doc = new DOMDocument( '1.0', 'utf-8' );
                /// @todo !important suppress errors in the loadXML call?
                if ( $doc->loadXML( $value ) )
                {
                    // fix 1st link objects
                    /// @see eZXMLTextType::unserializeContentObjectAttribute
                    $links = $doc->getElementsByTagName( 'link' );
                    foreach ( $links as $linkNode )
                    {
                        $href = $linkNode->getAttribute( 'href' );
                        if ( !$href )
                            continue;
                        $urlObj = eZURL::urlByURL( $href );

                        if ( !$urlObj )
                        {
                            $urlObj = eZURL::create( $href );
                            $urlObj->store();
                        }

                        $linkNode->removeAttribute( 'href' );
                        $linkNode->setAttribute( 'url_id', $urlObj->attribute( 'id' ) );
                        $urlObjectLink = eZURLObjectLink::create( $urlObj->attribute( 'id' ),
                                                                  $attribute->attribute( 'id' ),
                                                                  $attribute->attribute( 'version' ) );
                        $urlObjectLink->store();
                    }

                    // then all remote ids
                    $embeds = $doc->getElementsByTagName( 'embed' );
                    $objects = $doc->getElementsByTagName( 'object' );
                    $embedsInline = $doc->getElementsByTagName( 'embed-inline' );

                    self::transformRemoteLinksToLinks( $links, $attribute );
                    self::transformRemoteLinksToLinks( $embeds, $attribute );
                    self::transformRemoteLinksToLinks( $objects, $attribute );
                    self::transformRemoteLinksToLinks( $embedsInline, $attribute );
                }
                else
                {
                    $attrname = $attribute->attribute( 'contentclass_attribute_identifier' );
                    eZDebug::writeWarning( "Can not import xml text because content is not valid xml in attribute $attrname", __METHOD__ );

                    $parser = new eZXMLInputParser();
                    $doc = $parser->createRootNode();
                    //$xmlText = eZXMLTextType::domString( $doc );
                }
                $attribute->fromString( $doc->saveXML() );
                break;

            default:
                $attribute->fromString( $value );

        }

        // nb: most fromstring calls return null...
        if ( $ok !== false )
        {
            $attribute->store();
        }
        return $ok;
    }

    /// Taken from eZXMLTextType::transformLinksToRemoteLinks
    protected static function transformLinksToRemoteLinks( DOMNodeList $nodeList, $ridGenerator )
    {
        foreach ( $nodeList as $node )
        {
            $linkID = $node->getAttribute( 'url_id' );
            $isObject = ( $node->localName == 'object' );
            $objectID = $isObject ? $node->getAttribute( 'id' ) : $node->getAttribute( 'object_id' );
            $nodeID = $node->getAttribute( 'node_id' );

            if ( $linkID )
            {
                $urlObj = eZURL::fetch( $linkID );
                if ( !$urlObj ) // an error occured
                {
                    /// @todo log warning
                    continue;
                }
                $url = $urlObj->attribute( 'url' );
                $node->setAttribute( 'href', $url );
                $node->removeAttribute( 'url_id' );
            }
            elseif ( $objectID )
            {
                $object = eZContentObject::fetch( $objectID, false );
                if ( is_array( $object ) )
                {
                    $node->setAttribute( 'object_remote_id', $ridGenerator->buildRemoteId( $objectID, $object['remote_id'], 'object' ) );
                }
                /// @todo log warning if not found

                if ( $isObject )
                {
                    $node->removeAttribute( 'id' );
                }
                else
                {
                    $node->removeAttribute( 'object_id' );
                }
            }
            elseif ( $nodeID )
            {
                $nodeData = eZContentObjectTreeNode::fetch( $nodeID, false, false );
                if ( is_array( $nodeData ) )
                {
                    $node->setAttribute( 'node_remote_id',  $ridGenerator->buildRemoteId( $nodeID, $nodeData['remote_id'], 'node' ) );
                }
                /// @todo log warning if not found

                $node->removeAttribute( 'node_id' );
            }
        }
    }

    /// Taken from eZXMLTextType::transformRemoteLinksToLinks
    protected static function transformRemoteLinksToLinks( DOMNodeList $nodeList, eZContentObjectAttribute $attribute )
    {
        //$modified = false;

        $contentObject = $attribute->attribute( 'object' );
        foreach ( $nodeList as $node )
        {
            $objectRemoteID = $node->getAttribute( 'object_remote_id' );
            $nodeRemoteID = $node->getAttribute( 'node_remote_id' );
            if ( $objectRemoteID )
            {
                $objectArray = eZContentObject::fetchByRemoteID( $objectRemoteID, false );
                if ( !is_array( $objectArray ) )
                {
                    eZDebug::writeWarning( "Can't fetch object with remoteID = $objectRemoteID", __METHOD__ );
                    continue;
                }

                $objectID = $objectArray['id'];
                if ( $node->localName == 'object' )
                    $node->setAttribute( 'id', $objectID );
                else
                    $node->setAttribute( 'object_id', $objectID );
                $node->removeAttribute( 'object_remote_id' );
                //$modified = true;

                // add as related object
                if ( $contentObject )
                {
                    $relationType = $node->localName == 'link' ? eZContentObject::RELATION_LINK : eZContentObject::RELATION_EMBED;
                    $contentObject->addContentObjectRelation( $objectID, $attribute->attribute( 'version' ), 0, $relationType );
                }
            }
            elseif ( $nodeRemoteID )
            {
                $nodeArray = eZContentObjectTreeNode::fetchByRemoteID( $nodeRemoteID, false );
                if ( !is_array( $nodeArray ) )
                {
                    eZDebug::writeWarning( "Can't fetch node with remoteID = $nodeRemoteID", __METHOD__ );
                    continue;
                }

                $node->setAttribute( 'node_id', $nodeArray['node_id'] );
                $node->removeAttribute( 'node_remote_id' );
                //$modified = true;

                // add as related object
                if ( $contentObject )
                {
                    $relationType = $node->nodeName == 'link' ? eZContentObject::RELATION_LINK : eZContentObject::RELATION_EMBED;
                    $contentObject->addContentObjectRelation( $nodeArray['contentobject_id'], $attribute->attribute( 'version' ), 0, $relationType );
                }
            }
        }

        //return $modified;
    }
}
