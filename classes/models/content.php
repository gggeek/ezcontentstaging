<?php
/**
 * The contentStagingVersion class is used to provide the representation of a Content
 * (an object) used in REST api calls.
 *
 * It mainly takes care of
 * 1. exposing the needed attributes and casting each of them in the correct type
 * 2. exposing methods to manipulate content taking structured arrays as input
 *
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class contentStagingContent extends contentStagingBase
{
    public $contentType;
    public $name;
    public $id;
    public $ownerId;
    public $sectionId;
    public $state;
    public $versionNo;
    public $creatorId;
    public $created;
    public $modified;
    public $alwaysAvailable;
    public $remoteId;
    public $locationIds;
    public $fields;

    function __construct( eZContentObject $object )
    {
        $this->contentType = $object->attribute( 'class_identifier' );
        $this->name = $object->attribute( 'name' );
        $this->id = (int)$object->attribute( 'id' );
        $this->ownerId = (int)$object->attribute( 'owner_id' );
        $this->sectionId = (int)$object->attribute( 'section_id' );

        switch( $object->attribute( 'status' ) )
        {
            case eZContentObject::STATUS_DRAFT:
                $this->state = 'DRAFT';
                break;
            case eZContentObject::STATUS_ARCHIVED:
                $this->state = 'ARCHIVED';
                break;
            default:
                $this->state = 'PUBLISHED';
        }

        $this->versionNo = (int)$object->attribute( 'current_version' );
        $this->creatorId = (int)$object->attribute( 'current' )->attribute( 'creator_id' );
        $this->created = (int)$object->attribute( 'published' );
        $this->modified = (int)$object->attribute( 'modified' );
        $this->alwaysAvailable = (bool)$object->attribute( 'always_available' );
        $this->remoteId = $object->attribute( 'remote_id' );

        $this->locationIds = array();
        /// @todo this is bad for performances, we should not fetch full nodes
        foreach ( $object->attribute( 'assigned_nodes' ) as $node )
        {
            $this->locationIds[] = (int)$node->attribute( 'node_id' );
        }

        $this->fields = array();
        foreach ( $object->attribute( 'data_map' ) as $identifier => $attr )
        {
            $type = $attr->attribute( 'data_type_string' );
            switch( $type )
            {
                default:
                    $value = $attr->toString();
            }
            $this->fields[$identifier] = array(
                'fieldDef' => $type,
                'id' => (int)$attr->attribute( 'id' ),
                'value' => $value,
                'language' => $attr->attribute( 'language_code' )
            );
        }
    }


    /**
    * Updates the $object with the provided fields in the request
    *
    * @param eZContentObject $object
    * @return eZContentObject
    */
    static function updateContent( eZContentObject $object, $input )
    {
        //$input = $this->request->inputVariables;
        $db = eZDB::instance();
        $db->begin();
        $version = $object->createNewVersionIn( $input['localeCode'] );
        $version->setAttribute( 'modified', time() );
        $version->store();

        self::updateAttributesList(
            $version->attribute( 'contentobject_attributes' ),
            $input['fields']
        );
        $db->commit();

        $operationResult = eZOperationHandler::execute(
        'content', 'publish',
        array(
            'object_id' => $object->attribute( 'id' ),
            'version' => $version->attribute( 'version' )
         )
        );
        return eZContentObject::fetch( $object->attribute( 'id' ) );
    }

    /**
     * Updates the eZContentObjectAttribute in $attributes with the values
     * provided in $fields
     *
     * @param array $attributes array of eZContentObjectAttribute to update
     * @param array $fields
     */
    protected static function updateAttributesList( array $attributes, array $fields )
    {
        foreach ( $attributes as $attribute )
        {
            $identifier = $attribute->attribute( 'contentclass_attribute_identifier' );
            if ( !isset( $fields[$identifier] ) )
            {
                continue;
            }
            $field = $fields[$identifier];
            switch( $field['fieldDef'] )
            {
                case 'ezimage':
                case 'ezbinaryfile':
                case 'ezmedia':
                {
                    $tmpDir = eZINI::instance()->variable( 'FileSettings', 'TemporaryDir' ) . '/' . uniqid();
                    // todo use the origin filename when it'll be available
                    $fileName = uniqid();

                    eZFile::create( $fileName, $tmpDir, base64_decode( $field['value'] ) );
                    $field['value'] = $tmpDir . '/' . $fileName;
                }
                default:
            }
            $attribute->fromString( $field['value'] );
            $attribute->store();
            if ( isset( $tmpDir ) )
            {
                eZDir::recursiveDelete( $tmpDir, false );
                unset( $tmpDir );
            }
        }
    }

    /**
     * Create a content under $parent with the input variables
     *
     * @param eZContentObjectTreeNode $parent
     * @return eZContentObject
     */
    static function createContent( eZContentObjectTreeNode $parent, $input, $sectionId=null )
    {
        //$input = $this->request->post; // shouldn't it be inputVariables ? but it's empty ?
        $class = eZContentClass::fetchByIdentifier( $input['contentType'] );
        if ( !$class instanceof eZContentClass )
        {
            throw new RuntimeException(
                'Unable to load the class with identifier ' . $input['contentType']
            );
        }
        $db = eZDB::instance();
        $db->begin();
        $content = $class->instantiateIn( $input['initialLanguage'] );
        $content->setAttribute( 'remote_id', $input['remoteId'] );
        $content->store();

        $nodeAssignment = eZNodeAssignment::create(
        array(
            'contentobject_id' => $content->attribute( 'id' ),
            'contentobject_version' => $content->attribute( 'current_version' ),
            'parent_node' => $parent->attribute( 'node_id' ),
            'is_main' => 1,
            'sort_field' => $class->attribute( 'sort_field' ),
            'sort_order' => $class->attribute( 'sort_order' )
        )
        );
        $nodeAssignment->store();

        $version = $content->version( 1 );
        $version->setAttribute( 'modified', time() );
        $version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
        $version->store();

        $attributes = $content->contentObjectAttributes( true, false, $input['initialLanguage'] );
        self::updateAttributesList( $attributes, $input['fields'] );
        $db->commit();

        $operationResult = eZOperationHandler::execute(
        'content', 'publish',
        array(
            'object_id' => $content->attribute( 'id' ),
            'version' => 1
         )
        );
        return $content;
    }

    /**
     * Create a new location for the $object under the $parent node.
     *
     * @param eZContentObject $object
     * @param eZContentObjectTreeNode $parent
     * @param string $newNodeRemoteId
     * @param int $priority
     * @param int $sortField
     * @param int $sortOrder
     * @return eZContentObjectTreeNode
     */
    static function addAssignment( eZContentObject $object, eZContentObjectTreeNode $parent, $newNodeRemoteId, $priority, $sortField, $sortOrder )
    {
        $sortField = self::getSortField( $sortField );
        $sortOrder = self:: getSortOrder( $sortOrder);

        /// @todo do we need a transaction here?
        $db = eZDB::instance();
        $db->begin();
        $newNode = $object->addLocation( $parent->attribute( 'node_id' ), true );
        $newNode->setAttribute( 'contentobject_is_published', 1 );
        $newNode->setAttribute( 'main_node_id', $object->attribute( 'main_node_id' ) );
        $newNode->setAttribute( 'remote_id', $newNodeRemoteId );
        $newNode->setAttribute( 'priority', $priority );
        $newNode->setAttribute( 'sort_field', $sortField );
        $newNode->setAttribute( 'sort_order', $sortOrder );
        // Make sure the url alias is set updated.
        $newNode->updateSubTreePath();
        $newNode->sync();
        $db->commit();
        return $newNode;
    }

    /**
     * Update the remote id of the $node
     *
     * @param eZContentObjectTreeNode $node
     * @param string $remoteId
     */
    static function updateRemoteId( eZContentObject $object, $remoteId )
    {
        $object->setAttribute( 'remote_id', $remoteId );
        $object->store();
        eZContentCacheManager::clearContentCache(
            $object->attribute( 'id' )
        );
    }

}

