<?php
/**
 * The contentStagingContent class is used to provide the representation of a Content
 * (an object) used in REST api calls.
 *
 * It mainly takes care of exposing the needed attributes and casting each of
 * them in the right type.
 *
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class contentStagingContent
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
        $this->creatorId = (int)$this->ownerId;
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
        $this->created = 0; // ??
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



}

