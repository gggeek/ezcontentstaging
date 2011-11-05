<?php
/**
 * contentStagingLocation class is used to provide the representation of a Location
 * (a node) used in REST api calls.
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

class contentStagingLocation
{
    public $pathString;
    public $pathIdentificationString;
    public $id;
    public $contentId;
    public $parentId;
    public $mainLocationId;
    public $priority;
    public $hidden;
    public $depth;
    public $invisible;
    public $modifiedSubLocation;
    public $children;
    public $sortField;
    public $sortOrder;

    function __construct( eZContentObjectTreeNode $node )
    {
        $this->pathString = $node->attribute( 'path_string' );
        $this->pathIdentificationString = $node->attribute( 'path_identification_string' );
        $this->id = (int)$node->attribute( 'node_id' );
        $this->contentId = (int)$node->attribute( 'contentobject_id' );
        $this->parentId = (int)$node->attribute( 'parent_node_id' );
        $this->mainLocationId = (int)$node->attribute( 'main_node_id' );
        $this->priority = (int)$node->attribute( 'priority' );
        $this->hidden = (bool)$node->attribute( 'hidden' );
        $this->depth = (int)$node->attribute( 'depth' );
        $this->invisible = (bool)$node->attribute( 'invisible' );
        $this->modifiedSubLocation = (int)$node->attribute( 'modified_subnode' ); // @todo type: "date" ?

        $this->children = array(); // @todo not filled for now

        $this->sortOrder = 'ASC';
        if ( $node->attribute( 'sort_order' ) == eZContentObjectTreeNode::SORT_ORDER_DESC )
            $this->sortOrder = 'DESC';

        $this->sortField = strtoupper(
            eZContentObjectTreeNode::sortFieldName( $node->attribute( 'sort_field' ) )
        );
        if ( $this->sortField === null )
            $this->sortField = 'PATH';
    }



}

