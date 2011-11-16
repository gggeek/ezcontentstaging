<?php
/**
 * The contentStagingLocation class is used to provide the representation of a Location
 * (a node) used in REST api calls.
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

class eZContentStagingLocation extends contentStagingBase
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
    public $remoteId;
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
        $this->remoteId = $node->attribute( 'remote_id' );
        $this->modifiedSubLocation = self::encodeDatetIme( $node->attribute( 'modified_subnode' ) );

        $this->children = array();
        /// @todo optimize: do not load all children just to get their ids
        foreach ( $node->attribute( 'children' ) as  $child )
        {
            $this->children[] = (int)$child->attribute( 'node_id' );
        }

        $this->sortOrder = self::encodeSortOrder( $node->attribute( 'sort_order' ) );

        $this->sortField = self::encodeSortField( $node->attribute( 'sort_field' ) );
    }

    /**
     * Update the sort order and the sort field of the $node
     *
     * @param eZContentObjectTreeNode $node
     * @param int $sortField
     * @param int $sortOrder
     */
    static function updateSort( eZContentObjectTreeNode $node, $sortField, $sortOrder )
    {
        $sortField = self::decodeSortField( $sortField );
        $sortOrder = self::decodeSortOrder( $sortOrder);

        $db = eZDB::instance();
        $handling = $db->setErrorHandling( eZDB::ERROR_HANDLING_EXCEPTIONS );
        try
        {
            $node->setAttribute( 'sort_field', $sortField );
            $node->setAttribute( 'sort_order', $sortOrder );
            $node->store();
            eZContentCacheManager::clearContentCache(
                $node->attribute( 'contentobject_id' )
            );
            return 0;
        }
        catch ( exception $e )
        {
            return $e->getMessage();
        }
    }

    /**
     * Update the priority of the $node
     *
     * @param eZContentObjectTreeNode $node
     * @param int $priority
     */
    static function updatePriority( eZContentObjectTreeNode $node, $priority )
    {
        $db = eZDB::instance();
        $handling = $db->setErrorHandling( eZDB::ERROR_HANDLING_EXCEPTIONS );
        try
        {
            $node->setAttribute( 'priority', $priority );
            $node->store();
            eZContentCacheManager::clearContentCache(
                $node->attribute( 'contentobject_id' )
            );
            return 0;
        }
        catch ( exception $e )
        {
            return $e->getMessage();
        }
    }

    /**
     * Update the remote id of the $node
     *
     * @param eZContentObjectTreeNode $node
     * @param string $remoteId
     */
    static function updateRemoteId( eZContentObjectTreeNode $node, $remoteId )
    {
        $db = eZDB::instance();
        $handling = $db->setErrorHandling( eZDB::ERROR_HANDLING_EXCEPTIONS );
        try
        {
            $node->setAttribute( 'remote_id', $remoteId );
            $node->store();
            eZContentCacheManager::clearContentCache(
                $node->attribute( 'contentobject_id' )
            );
            return 0;
        }
        catch ( exception $e )
        {
            return $e->getMessage();
        }
    }

    /**
     * Update the visibility of the $node
     *
     * @param eZContentObjectTreeNode $node
     * @param bool $hide
     */
    static function updateVisibility( eZContentObjectTreeNode $node, $hide )
    {
        if ( $hide )
        {
            eZContentObjectTreeNode::hideSubTree( $node );
        }
        else
        {
            eZContentObjectTreeNode::unhideSubTree( $node );
        }
    }
}

