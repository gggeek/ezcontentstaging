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
    * NB: if passed a $ridGenerator, all local obj/node ids are substituted with remote ones, otherwise not
    *
    * NB: we assume that attributes are not empty here - we leave the test for .has_content to the caller
    *
    * @param eZContentStagingRemoteIdGenerator $ridGenerator (or null)
    * @see serializeContentObjectAttribute and toString in different datatypes
    *      for datatypes that need special treatment
    * @todo implement this conversion within the datatypes themselves:
    *       it is a much better idea...
    */
    function __construct( eZContentObjectAttribute $attribute, $locale, $ridGenerator )
    {
        $this->fieldDef = $attribute->attribute( 'data_type_string' );
        $this->language = $locale;
        switch( $this->fieldDef )
        {
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
                    $this->value = $attribute->toString();
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

                // known bug in ezuser serialization: #018609
            case 'ezuser':

            // see also http://issues.ez.no/IssueList.php?Search=fromstring&SearchIn=1
            // see also http://issues.ez.no/IssueList.php?Search=tostring&SearchIn=1
            default:
                $this->value = $attribute->toString();
        }
    }

    /**
    * @todo implement all missing validation that happens when we go via fromString...
    *
    * @todo decide: shall we throw an exception if data does not validate or just emit a warning?
    *
    * @see eZDataType::unserializeContentObjectAttribute
    * @see eZDataType::fromstring
    */
    static function decodeValue( $attribute, $value )
    {
        switch( $value['fieldDef'] )
        {
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
                        eZDebug::writeWarning( "Can not create relation because object with remote id {$value['remoteId']} is missing", __METHOD__ );
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
                            eZDebug::writeWarning( "Can not create relation because object with remote id {$item['remoteId']} is missing", __METHOD__ );
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



            case 'ezbinaryfile':
            case 'ezmedia':
            case 'ezimage':
                if ( !is_array( $value ) || !isset( $value['fileName'] ) || !isset( $value['content'] ) )
                {
                    eZDebug::writeWarning( "Can not create binary file because fileName or content is missing", __METHOD__ );
                    $ok = false;
                    break;
                }

                $tmpDir = eZINI::instance()->variable( 'FileSettings', 'TemporaryDir' ) . '/' . uniqid() . '-' . microtime( true );
                $fileName = $value['fileName'];
                /// @todo test if base64 decoding fails and if decoded img filesize is ok
                eZFile::create( $fileName, $tmpDir, base64_decode( $value['content'] ) );

                $path = "$tmpDir/$fileName";
                if ( $value['fieldDef'] == 'image' )
                {
                    $path .= "|{$value['alternativeText']}";
                }
                $ok = $attribute->fromString( $path );

                if ( $ok && $value['fieldDef'] == 'ezmedia' )
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

            /*case 'ezimage':
                /// @todo use a timestamp added to process pid for temp dir to avoid race conds
                $tmpDir = eZINI::instance()->variable( 'FileSettings', 'TemporaryDir' ) . '/' . uniqid();
                $fileName = $value['fileName'];
                /// @todo test if base64 decoding fails and if decoded img filesize is ok
                eZFile::create( $fileName, $tmpDir, base64_decode( $value['content'] ) );

                $ok = $attribute->fromString( "$tmpDir/$fileName|{$value['alternativeText']}" );
                /*$content = $attribute->attribute( 'content' );
                $content->initializeFromFile( "$tmpDir/$fileName" );
                $content->setAttribute( 'alternative_text',  $value['alternativeText'] );
                $content->store( $attribute );* /

                eZDir::recursiveDelete( $tmpDir, false );
                break;*/

            default:
                $ok = $attribute->fromString( $value );

        }

        if ( $ok )
        {
            $attribute->store();
        }
        return $ok;
    }

}
