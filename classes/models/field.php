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
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
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
    *       it is a much better idea... (check datatypes that support a fromHash (fromJson?) method, use it)
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
                if ( $attribute->attribute( 'has_content' ) )
                {
                    $content = $attribute->attribute( 'content' );
                    $file = eZClusterFileHandler::instance( $content->attribute( 'filepath' ) );
                    /// @todo for big files, we should do piecewise base64 encoding, or we might go over memory limit
                    $this->value = array(
                        'fileSize' => (int)$content->attribute( 'filesize' ),
                        'fileName' => $content->attribute( 'original_filename' ),
                        'content' => base64_encode( $file->fetchContents() )
                        );
                }
                break;

            case 'ezboolean':
                // nb: the ezbbolean datatype does not support 'not having' content
                $this->value = (bool) $attribute->toString();
                break;

            case 'ezcountry':
                if ( $attribute->attribute( 'has_content' ) )
                {
                    $this->value = explode( ',', $attribute->toString() );
                }
                break;

            /// @todo shall we use iso 8601 format for dates?
            case 'ezdate':
            case 'ezdatetime':
                $this->value = (int) $attribute->toString();
                break;

            /// @todo serialize with wanted precision, using json native float type
            case 'ezfloat':
                if ( $attribute->attribute( 'has_content' ) )
                {
                    $this->value = $attribute->toString();
                }
                else
                {
                    $this->value = array();
                }
                break;

            case 'ezgmaplocation':
                if ( $attribute->attribute( 'has_content' ) )
                {
                    $gmaplocation = $attribute->attribute( 'content' );
                    $this->value = array(
                        "latitude" => $gmaplocation->attribute( 'latitude' ),
                        "longitude" =>  $gmaplocation->attribute( 'longitude' ),
                        "address" =>  $gmaplocation->attribute( 'address' )
                    );
                }
                break;

            case 'ezidentifier':
                if ( $attribute->attribute( 'has_content' ) )
                {
                    $this->value = $attribute->toString();
                }
                else
                {
                    $this->value = "";
                }
                break;

            // serialized as a struct
            case 'ezimage':
                if ( $attribute->attribute( 'has_content' ) )
                {
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
                }
                break;

            case 'ezinteger':
                if ( $attribute->attribute( 'has_content' ) )
                {
                    $this->value = (int) $attribute->toString();
                }
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
                if ( $attribute->attribute( 'has_content' ) )
                {
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
                }
                break;

            // serialized as a single string of either local or remote id
            case 'ezobjectrelation':
                // slightly more intelligent than base "toString" method: we always check for presence of related object
                $relatedObjectID = $attribute->attribute( 'data_int' );
                if ( $relatedObjectID )
                {
                    $relatedObject = eZContentObject::fetch( $relatedObjectID );
                    if ( $relatedObject )
                    {
                        if ( $ridGenerator )
                        {
                            $this->value = 'remoteId:' . $ridGenerator->buildRemoteId( $relatedObjectID, $relatedObject->attribute( 'remote_id' ), 'object' );
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
                        $values[] = 'remoteId:' . $ridGenerator->buildRemoteId( $relatedObjectInfo['contentobject_id'], $relatedObjectInfo['contentobject_remote_id'], 'object' );
                    }
                    $this->value = $values;
                }
                else
                {
                    $this->value = explode( '-', $attribute->toString() );
                }
                break;

            case 'ezpage':
                // Default toString() call encodes block definitions, not block contents,
                // so we encode by hand definition of all block items; this is necessary for manual blocks.
                // Also we need to patch in the block parameters the node ids with remote ids
                $zones = array();
                $blockItems = array();
                $attributes = array();

                // 1. encode all (scalar) page attributes
                $page = $attribute->attribute( 'content' );
                foreach( $page->attributes() as $attrname )
                {
                    if ( $attrname != 'zones' )
                    {
                        $attributes[$attrname] = $page->attribute( $attrname );
                    }
                }

                // 2. encode zones
                foreach( $page->attribute( 'zones' ) as $zone )
                {
                    // 2.1 all (scalar) attributes
                    $zoneArray = array();
                    foreach( $zone->attributes() as $attrname )
                    {
                        if ( $attrname != 'id' && $attrname != 'blocks' )
                        {
                            $zoneArray[$attrname] = $zone->attribute( $attrname );
                        }
                    }

                    // 2.2 encode zone blocks
                    $zoneBlocks = array();
                    $blocks = $zone->attribute( 'blocks' );
                    if ( is_array( $blocks ) )
                    {
                        foreach ( $blocks as  $block )
                        {
                            $blockArray = array();

                            $blockType = $block->attribute( 'type' );
                            $ini = eZINI::instance( 'block.ini' );
                            if ( $ini->hasGroup( $blockType ) )
                            {
                                // 2.2.1 if block type is manual, we need to transport over its items
                                if ( $ini->hasVariable( $blockType, 'ManualAddingOfItems' ) && $ini->variable( $blockType, 'ManualAddingOfItems' ) == 'enabled' )
                                {
                                    /// q: shall we also encode archived nodes?
                                    $blockItems[$block->attribute( 'id' )] = array(
                                        'valid' => self::transformBlockItemsToRemote( $block->attribute( 'valid' ), $ridGenerator ),
                                        'waiting' => self::transformBlockItemsToRemote( $block->attribute( 'waiting' ), $ridGenerator )
                                    );
                                    $manual = true;
                                }
                                else
                                {
                                    $manual = false;
                                }

                                // 2.2.2 encode block params - transcoding any which is a node id
                                foreach( $block->attributes() as $attrname )
                                {
                                    if ( !in_array( $attrname, array( 'id', 'items', 'zone_id', 'waiting', 'valid', 'valid_nodes', 'archived', 'view_template', 'edit_template', 'last_valid_item' ) ) )
                                    {
                                        if ( $attrname == 'fetch_params' && $ridGenerator)
                                        {
                                            $params = unserialize( $block->attribute( $attrname ) );
                                            $paramTypes = $ini->hasVariable( $blockType, 'FetchParameters' ) ? $ini->variable( $blockType, 'FetchParameters' ) : array();
                                            foreach ( $params as $name => $value )
                                            {
                                                if ( isset( $paramTypes[$name] ) && $paramTypes[$name] == 'NodeID' )
                                                {
                                                    $node = eZContentObjectTreeNode::fetch( $value );
                                                    if ( $node )
                                                    {
                                                        $params[$name] = 'remoteId:' . $ridGenerator->buildRemoteId( $value, $node->attribute( 'remote_id' ) );
                                                    }
                                                    else
                                                    {
                                                        eZDebug::writeError( '', __METHOD__ );
                                                    }
                                                }
                                            }
                                            $blockArray[$attrname] = serialize( $params );
                                        }
                                        else if ( $attrname == 'custom_attributes' && $ridGenerator )
                                        {
                                            $params = $block->attribute( $attrname );
                                            $paramTypes = $ini->hasVariable( $blockType, 'UseBrowseMode' ) ? $ini->variable( $blockType, 'UseBrowseMode' ) : array();
                                            foreach ( $params as $name => $value  )
                                            {
                                                if ( isset( $paramTypes[$name] ) && $paramTypes[$name] == 'true' )
                                                {
                                                    $node = eZContentObjectTreeNode::fetch( $value );
                                                    if ( $node )
                                                    {
                                                        $params[$name] = 'remoteId:' . $ridGenerator->buildRemoteId( $value, $node->attribute( 'remote_id' ) );
                                                    }
                                                    else
                                                    {
                                                        eZDebug::writeError( '', __METHOD__ );
                                                    }
                                                }
                                            }
                                            $blockArray[$attrname] = $params;
                                        }
                                        else
                                        {
                                            $blockArray[$attrname] = $block->attribute( $attrname );
                                        }
                                    }
                                }

                                $zoneBlocks[$block->attribute( 'id' )] = $blockArray;
                            }
                            else
                            {
                                eZDebug::writeWarning( "Block type $blockType found for staging export of an ezpage attribute, but no block definition in block.ini", __METHOD__ );
                            }
                        }
                    }
                    $zoneArray['blocks'] = $zoneBlocks;

                    $zones[$zone->attribute( 'id' )] = $zoneArray;
                }

                /// @todo the xml should be abandoned in favor of nested data
                $this->value = array(
                    'xml' => $attribute->toString(),
                    'attributes' => $attributes,
                    'zones' => $zones,
                    'block_items' => $blockItems
                );
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
                        eZDebug::writeError( "Cannot encode attribute for push to staging server: invalid xml", __METHOD__ );
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
                if ( $attribute->attribute( 'has_content' ) )
                {
                    $this->value = array(
                        'url' => eZURL::url( $attribute->attribute( 'data_int' ) ),
                        'text' => $attribute->attribute( 'data_text' ) );
                }
                break;

            // known bug in ezuser serialization: #018609
            case 'ezuser':

            default:
                $this->value = $attribute->toString();
        }
    }

    /**
    * NB: we assume that someone else has checked for proper type matching between attr. and value
    * This method supports receiving a null value to clear the current attribute
    * (eg. when used in updating an object to version 2, removing the url from
    * an ezurl attribute or the link from an ezobjectrelation attribute)
    *
    * @todo implement all missing validation that does not happen when we go via fromString...
    * @todo decide: shall we throw an exception if data does not validate or just emit a warning?
    * @todo check datatypes that support a fromHash method, use it instead of hard-coded conversion here
    *       (but the name of that method should not exist yet anywhere in the wild for extensions...)
    *
    * @see eZDataType::unserializeContentObjectAttribute
    * @see eZDataType::fromString
    * @see http://issues.ez.no/IssueList.php?Search=fromstring
    */
    static function decodeValue( $attribute, $value )
    {
        $ok = true;

        $type = $attribute->attribute( 'data_type_string' );
        switch( $type )
        {
            case 'ezauthor':
                $author = new eZAuthor( );
                /// @todo check datatype: are email/name mandatory?
                foreach ( $value as $authorData )
                {
                    $author->addAuthor( -1, $authorData['name'], $authorData['email'] );
                }
                $attribute->setContent( $author );
                break;

            case 'ezbinaryfile':
            case 'ezmedia':
            case 'ezimage':
                if ( !$value )
                {
                    $version = $attribute->attribute( "version" );
                    $type = ( $type == 'ezbinaryfile' ? new eZBinaryFileType() : ( $type == 'ezmedia' ? new eZMediaType() : new eZImageType() ) );
                    $type->deleteStoredObjectAttribute( $attribute, $version );
                    break;
                }

                /// @todo convert to exception
                if ( !isset( $value['fileName'] ) || !isset( $value['content'] ) )
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

            case 'ezcountry':
                if ( $value == null )
                {
                     $contentObjectAttribute->setAttribute( 'data_text', '' );
                }
                else
                {
                    /// @todo check we received an array
                    /// @todo we should not allow the array to have more than 1 country, based on call definition
                    $attribute->fromString( implode( ",", $value ) );
                }
                break;

            /// @todo add min, max validation
            //case 'ezfloat':
            //  break;

            // serialized as array instead of single string
            case 'ezgmaplocation':
                if ( $value == null )
                {
                    $attribute->setAttribute( 'data_int', 0 );
                }
                else
                {
                    /// @todo check for presence and format of the 3 fields
                    $location = new eZGmapLocation( array(
                        'contentobject_attribute_id' => $attribute->attribute( 'id' ),
                        'contentobject_version' => $attribute->attribute( 'version' ),
                        'latitude' => $value['latitude'],
                        'longitude' => $value['longitude'],
                        'address' => $value['address']
                    ) );
                    $attribute->setContent( $location );
                    $attribute->setAttribute( 'data_int', 1 );
                }

                break;

            case 'ezidentifier':
                if ( $value == null )
                {
                    $contentClassAttribute = $attribute->attribute( 'contentclass_attribute' );
                    /// @todo test if all went well
                    $ok = eZIdentifierType::assignValue( $contentClassAttribute, $attribute );
                }
                else
                {
                    /// @todo check for uniqueness, format conformance
                   $attribute->fromString( $value );
                }
                break;

            /// @todo validate format: either isbn13 or 10
            //case 'ezisbn':
            //    break;

            case 'ezkeyword':
                $attribute->fromString( implode( ',', $value ) );
                break;

            /// @todo add min, max validation
            //case 'ezinteger':
            //  break;

            case 'ezobjectrelation':
                if ( $value ==  null )
                {
                    // native fromstring does not reset to non-linked status
                    $attribute->setAttribute( 'data_int', 0 );
                }
                else
                {
                    /// @todo throw exception instead of returning false
                    if ( strpos( 'remoteId:', $value ) == 0 )
                    {
                        $value = substr( $value, 9 );
                        $object = eZContentObject::fetchByRemoteId( $value );
                        if ( $object )
                        {
                            // avoid going via fromstring for a small speed gain
                            $attribute->setAttribute( 'data_int', $object->attribute( 'id' ) );
                            $ok = true;
                        }
                        else
                        {
                            $attrname = $attribute->attribute( 'contentclass_attribute_identifier' );
                            eZDebug::writeWarning( "Can not create relation because object with remote id {$value} is missing in attribute $attrname", __METHOD__ );
                            $ok = false;
                        }
                    }
                    else
                    {
                        $ok = $attribute->fromString( $value );
                    }
                }
                break;

            case 'ezobjectrelationlist':
                $localIds = array();
                foreach( $value as $key => $item )
                {
                    if ( strpos( 'remoteId:', $item ) == 0 )
                    {
                        $item = substr( $item, 9 );
                        $object = eZContentObject::fetchByRemoteId( $item );
                        if ( $object )
                        {
                            $localIds[] = $object->attribute( 'id' );
                        }
                        else
                        {
                            $attrname = $attribute->attribute( 'contentclass_attribute_identifier' );
                            eZDebug::writeWarning( "Can not create relation because object with remote id {$item} is missing in attribute $attrname", __METHOD__ );
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

            case 'ezpage':
                // load in the datatype the xml representation of the blocks
                /// @todo fixup in parameters the source of node ids - use json representation to rebuild the xml
                $attribute->fromString( $value['xml'] );

                $db = eZDB::instance();
                $db->begin();

                $currObject = $attribute->attribute( 'object' );
                $pageZones = $attribute->attribute( 'content' )->attribute( 'zones' );
                foreach( $pageZones as $pageZone )
                {
                    // 1: create missing blocks in the ezm_block table / update them if existing
                    $zoneBlocksIds = array();
                    $zoneBlocks = $pageZone->attribute( 'blocks' );
                    if ( is_array( $zoneBlocks ) )
                    {
                        foreach ( $zoneBlocks as $zoneBlock )
                        {
                            $zoneBlockId = $zoneBlock->attribute( 'id' );
                            $zoneBlocksIds[] = $zoneBlockId;
                            // We do not use eZPageBlock::fetch because it is brain dead
                            $flowBlock = eZFlowBlock::fetch( $zoneBlockId );
                            if ( !$flowBlock )
                            {
                                $rotation = $zoneBlock->attribute( 'rotation' );
                                $flowBlock = new eZFlowBlock( array(
                                    'id' => $zoneBlockId,
                                    'zone_id' => $pageZone->attribute( 'id' ),
                                    'name' => $zoneBlock->attribute( 'name' ),
                                    /// @bug we should actually link the block to current node, not to main one. What if object is multipositioned?
                                    /// @bug what if this is obj creation and there is no node yet?
                                    'node_id' => $currObject->attribute( 'main_node_id' ),
                                    'overflow_id' => $zoneBlock->attribute( 'overflow_id' ),
                                    //'last_update' => '',
                                    'block_type' => $zoneBlock->attribute( 'type' ),
                                    'fetch_params' => $zoneBlock->attribute( 'fetch_params' ),
                                    'rotation_type' => isset( $rotation['type'] ) ? $rotation['type'] : 0,
                                    'rotation_interval' => isset( $rotation['interval'] ) ? $rotation['interval'] : 0,
                                    //'is_removed' => ''
                                ) );
                                $flowBlock->store();
                            }
                            else
                            {
                                // we assume block id, zone_id, block_type can not be changed - as well as current node
                                $flowBlock->setAttribute( 'name', $zoneBlock->attribute( 'name' ) );
                                $flowBlock->setAttribute( 'overflow_id', $zoneBlock->attribute( 'overflow_id' ) );
                                $flowBlock->setAttribute( 'fetch_params', $zoneBlock->attribute( 'fetch_params' ) );
                                $flowBlock->setAttribute( 'rotation_type', isset( $rotation['type'] ) ? $rotation['type'] : 0 );
                                $flowBlock->setAttribute( 'rotation_interval', isset( $rotation['interval'] ) ? $rotation['interval'] : 0 );
                                $flowBlock->store();
                            }
                        }
                    }

                    // 1.1: delete from ezm_block those that are in current zone but actually not there anymore
                    /// @todo move to eZPO calls
                    if ( count( $zoneBlocksIds ) )
                    {
                        foreach ( $zoneBlocksIds as $i => $v  )
                        {
                            $zoneBlocksIds[$i] = $db->escapeString( $v );
                        }
                        $db->query( "DELETE from ezm_block WHERE zone_id='" . $db->escapeString( $pageZone->attribute( 'id' ) ) . "' AND id NOT IN ('" . implode( "', '", $zoneBlocksIds ). "')" );
                    }
                    else
                    {
                        eZPersistentObject::removeObject( eZFlowBlock::definition(), array( 'zone_id' => $pageZone->attribute( 'id' ) ) );
                    }

                }

                // 2. then add missing items in the ezm_pool table
                if ( isset( $value['block_items'] ) )
                {
                    foreach( $value['block_items'] as $blockId => $blockItems )
                    {
                        // 2.1 preliminary step: check if this block is really part of the page zones
                        $zoneBlock = false;
                        $pageZone = false;
                        foreach( $pageZones as $zone )
                        {
                            $pageZone = $zone;
                            $blocks = $zone->attribute( 'blocks' );
                            if ( is_array( $blocks ) )
                            {
                                foreach ( $blocks as $block )
                                {
                                    if ( $block->attribute( 'id' ) == $blockId )
                                    {
                                        $zoneBlock = $block;
                                        break 2;
                                    }
                                }
                            }
                        }
                        if ( !$zoneBlock )
                        {
                            eZDebug::writeWarning( "Can not import in ezpage items for block $blockId. It is not in the attribute serialized xml", __METHOD__ );
                            continue;
                        }
                        // 2.2 reset block items: remove all existing (we assume block is manual)
                        eZPersistentObject::removeObject( eZFlowPoolItem::definition(), array( 'block_id' => $blockId ) );

                        // 2.3 then add new ones
                        $goodItems = array();
                        foreach( $blockItems as $type => $typeArrary )
                        {
                            foreach( $typeArrary as $i => $blockItem )
                            {
                                $node = $object = false;
                                if ( isset( $blockItem['remote_node_id'] ) && isset( $blockItem['remote_object_id'] ) )
                                {
                                    $node = eZContentObjectTreeNode::fetchByRemoteID( $blockItem['remote_node_id'] );
                                    $object = eZContentObject::fetchByRemoteID( $blockItem['remote_object_id'] );
                                }
                                if ( !$node || !$object || $node->attribute( 'contentobject_id' ) != $object->attribute( 'id' ) )
                                {
                                    eZDebug::writeWarning( "Can not import in ezpage block node. remote Id: '{$blockItem['remote_node_id']}', obj remote Id: '{$blockItem['remote_object_id']}'", __METHOD__ );
                                    continue;
                                }
                                $goodItems[] = array(
                                    'blockID' => $blockId,
                                    'nodeID' => $node->attribute( 'node_id' ),
                                    'objectID' => $object->attribute( 'id' ),
                                    'priority' => $blockItem['priority'],
                                    'timestamp' => $blockItem['ts_publication']
                                );
                            }
                        }
                        if ( count( $goodItems ) )
                        {
                            eZFlowPool::insertItems( $goodItems );
                        }
                    }
                }

                $db->commit();
                break;

            case 'ezselection':
                /// @todo validate the uniqueness of the selection value as defined in content class
                /// @todo the fromString method silently discards all invalid selection keys. Shall we error out instead?
                $attribute->fromString( implode( '|', $value ) );
                break;

            case 'ezsrrating':
                $attribute->setAttribute( 'data_int', $value['can_rate'] );
                break;

            /// @todo validate max string length
            //case 'ezstring':
            //  break;

            case 'ezurl':
                if ( $value && @$value['url'] != '' )
                {
                    /// @todo reject requests without the 'url' parameter?
                    $urlID = eZURL::registerURL( $value['url'] );
                    $attribute->setAttribute( 'data_int', $urlID );
                    if( array_key_exists( 'text', $value ) )
                    {
                        $attribute->setAttribute( 'data_text', $value['text'] );
                    }
                }
                else
                {
                    // we do not delete url/urlobjectlink even if any was set previously,
                    // as this is done on publishing anyway...
                    $attribute->setAttribute( 'data_int', 0 );
                    $attribute->setAttribute( 'data_text', '' );
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
                if ( $value != "" && $doc->loadXML( $value ) )
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
                    if ( $value != "" )
                    {
                        $attrname = $attribute->attribute( 'contentclass_attribute_identifier' );
                        eZDebug::writeWarning( "Can not import xml text because content is not valid xml in attribute $attrname", __METHOD__ );
                    }

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

    /**
    * @param array $items array of array
    */
    protected static function transformBlockItemsToRemote( $items, $ridGenerator )
    {
        $out = array();
        foreach( $items as $i => $item )
        {
            $array = array();
            foreach( $item->attributes() as $key )
            {
                if ( $key != 'node_id' && $key != 'object_id' && $key != 'block_id' )
                {
                    $array[$key] = $item->attribute( $key );
                }
            }

            $node = eZContentObjectTreeNode::fetch( $item->attribute( 'node_id' ) );
            if ( $node )
            {
                $array['remote_node_id'] = $ridGenerator->buildRemoteId( $item->attribute( 'node_id' ), $node->attribute( 'remote_id' ) );
            }
            else
            {
                eZDebug::writeWarning( "Node {$item['node_id']} not found for staging export of an ezpage attribute, block " . $block->attribute( 'id' ), __METHOD__ );
            }

            $object = eZContentObject::fetch( $item->attribute( 'object_id' ) );
            if ( $object )
            {
                $array['remote_object_id'] = $ridGenerator->buildRemoteId( $item->attribute( 'object_id' ), $object->attribute( 'remote_id' ), 'object' );
            }
            else
            {
                eZDebug::writeWarning( "Object {$item['object_id']} not found for staging export of an ezpage attribute, block " . $block->attribute( 'id' ), __METHOD__ );
            }

            if ( $node && $object )
            {
                $out[] = $array;
            }
        }
        return $out;
    }
}
