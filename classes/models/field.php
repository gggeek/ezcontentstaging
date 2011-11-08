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
    * the constructor is where most of the magic happens
    * @see serializeContentObjectAttribute and toString in different datatypes
    *      for datatypes that need special treatment
    *
    * @todo implement this conversion within the datatypes themselves:
    *       it is a much better idea...
    */
    function __construct( eZContentObjectAttribute $attribute, $locale )
    {
        $this->datatype = $attribute->attribute( 'data_type_string' );
        $this->language = $locale;
        switch( $this->datatype )
        {
            case 'ezobjectrelation':
                $relatedObjectID = $attribute->attribute( 'content' );
                $relatedObject = eZContentObject::fetch( $relatedObjectID );
                if ( $relatedObject )
                {
                    $this->value = array( 'remoteId' => self::buildRemoteId( $relatedObjectID, $relatedObject->attribute( 'remote_id' ), 'object' ) );
                }
                else
                {
                    eZDebug::writeError( "Cannot encode attribute - related object $relatedObjectID not found for attribute in lang $locale", __METHOD__ );
                    $this->value = null;
                }
                break;

            case 'ezobjectrelationlist':
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
                    $values[] = array( 'remoteId' => self::buildRemoteId( $relatedObjectInfo['contentobject_id'], $relatedObjectInfo['contentobject_remote_id'], 'object' ) );
                }
                $this->value = $values;
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
                /// @todo for big files, we should do piecewise base64 encoding, or we go over memory limit
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
                /// @todo for big files, we should do piecewise base64 encoding, or we go over memory limit
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

}
