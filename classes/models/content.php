<?php

/**
 * contentStagingContent class is used to provide the result of REST API calls
 * that outputs a Content.
 *
 * It mainly takes care of exposing the needed attributes and casting each of
 * them in the right type.
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

        $this->state = 'PUBLISHED';
        if ( $object->attribute( 'status' ) == eZContentObject::STATUS_DRAFT )
            $this->state = 'DRAFT';
        elseif ( $object->attribute( 'status' ) == eZContentObject::STATUS_ARCHIVED )
            $this->state = 'ARCHIVED';

        $this->versionNo = (int)$object->attribute( 'current_version' );
        $this->created = 0; // ??
        $this->modified = (int)$object->attribute( 'modified' );
        $this->alwaysAvailable = (bool)$object->attribute( 'always_available' );
        $this->remoteId = $object->attribute( 'remote_id' );

        $this->locationIds = array();
        foreach ( $object->attribute( 'assigned_nodes' ) as $node )
        {
            $this->locationIds[] = (int)$node->attribute( 'node_id' );
        }

        $this->fields = array();
        foreach ( $object->attribute( 'data_map' ) as $identifier => $attr )
        {
            $this->fields[$identifier] = array(
                'fieldDef' => $attr->attribute( 'data_type_string' ),
                'id' => (int)$attr->attribute( 'id' ),
                'value' => $attr->toString(),
                'language' => $attr->attribute( 'language_code' )
            );
        }
    }



}

