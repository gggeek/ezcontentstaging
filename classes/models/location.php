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
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
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

    /**
     * Constructor
     *
     * @param eZContentObjectTreeNode $node
     *
     */
    public function __construct( eZContentObjectTreeNode $node )
    {
        $this->pathString = $node->attribute( 'path_string' );
        $this->pathIdentificationString = $node->attribute( 'path_identification_string' );
        $this->id = (int)$node->attribute( 'node_id' );
        $this->contentId = (int)$node->attribute( 'contentobject_id' );
        $this->parentId = (int)$node->attribute( 'parent_node_id' );
        $this->mainLocationId = (int)$node->attribute( 'main_node_id' );
        $this->priority = (int)$node->attribute( 'priority' );
        $this->hidden = (bool)$node->attribute( 'is_hidden' );
        $this->depth = (int)$node->attribute( 'depth' );
        $this->invisible = (bool)$node->attribute( 'is_invisible' );
        $this->remoteId = $node->attribute( 'remote_id' );
        $this->modifiedSubLocation = self::encodeDateTime( $node->attribute( 'modified_subnode' ) );

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
    static public function updateSort( eZContentObjectTreeNode $node, $sortField, $sortOrder )
    {
        $sortField = self::decodeSortField( $sortField );
        $sortOrder = self::decodeSortOrder( $sortOrder);

        if ( self::isTriggersExecutionEnabled() && eZOperationHandler::operationIsAvailable( 'content_sort' ) )
        {
            eZOperationHandler::execute(
                'content',
                'sort',
                array(
                    'node_id' => $node->attribute( 'node_id' ),
                    'sorting_field' => $sortField,
                    'sorting_order' => $sortOrder
                ),
                null,
                true
            );
            /// @todo test if any errors occurred
            return 0;
        }

        $result = eZContentOperationCollection::changeSortOrder( $node->attribute( 'node_id' ), $sortField, $sortOrder );
        return ( $result['status'] == true ) ? 0 : -1;
    }

    /**
     * Update the priority of the $node
     *
     * @param eZContentObjectTreeNode $node
     * @param int $priority
     *
     * @todo shall we fix priorities of other nodes too if there is a conflict?
     */
    static public function updatePriority( eZContentObjectTreeNode $node, $priority )
    {
        // note: the eZ API for this is mind-bending to say the least...
        $priorityArray = array( $priority );
        $priorityIDArray = array( $node->attribute( 'node_id') );

        if ( self::isTriggersExecutionEnabled() && eZOperationHandler::operationIsAvailable( 'content_updatepriority' ) )
        {
            eZOperationHandler::execute(
                'content',
                'updatepriority',
                array(
                    'node_id' => $node->attribute( 'parent_node_id' ),
                    'priority' => $priorityArray,
                    'priority_id' => $priorityIDArray
                ),
                null,
                true
            );
        }
        else
        {
            eZContentOperationCollection::updatePriority( $node->attribute( 'parent_node_id'), $priorityArray, $priorityIDArray );
        }
    }

    /**
     * Update the remote id of the $node
     *
     * @param eZContentObjectTreeNode $node
     * @param string $remoteId
     */
    static public function updateRemoteId( eZContentObjectTreeNode $node, $remoteId )
    {
        $db = eZDB::instance();
        $db->setErrorHandling( eZDB::ERROR_HANDLING_EXCEPTIONS );
        try
        {
            $node->setAttribute( 'remote_id', $remoteId );
            $node->store();
            eZContentCacheManager::clearContentCache(
                $node->attribute( 'contentobject_id' )
            );
            return 0;
        }
        catch ( Exception $e )
        {
            if ( $db->transactionCounter() )
            {
                $db->rollback();
            }
            return $e->getMessage();
        }
    }

    /**
     * Update the visibility of the $node
     *
     * @param eZContentObjectTreeNode $node
     * @param bool $hide
     *
     * @todo add checking for 'all ok'
     */
    static public function updateVisibility( eZContentObjectTreeNode $node, $hide )
    {
        if ( $node->attribute( 'is_hidden' ) == $hide )
            return;

        if ( self::isTriggersExecutionEnabled() && eZOperationHandler::operationIsAvailable( 'content_hide' ) )
        {
            eZOperationHandler::execute(
                'content',
                'hide',
                array( 'node_id' => $node->attribute( 'node_id' ) ),
                null,
                true
            );
        }
        else
        {
            eZContentOperationCollection::changeHideStatus( $node->attribute( 'node_id' ) );
        }
    }

    /**
     * Update main location of $node to $newMainLocation
     *
     * @param eZContentObjectTreeNode $node
     * @param eZContentObjectTreeNode $newMainLocation
     *
     * @todo check for failure
     */
    static public function updateMainLocation( eZContentObjectTreeNode $node, eZContentObjectTreeNode $newMainLocation )
    {
        if ( self::isTriggersExecutionEnabled() && eZOperationHandler::operationIsAvailable( 'content_updatemainassignment' ) )
        {
            eZOperationHandler::execute(
                'content',
                'updatemainassignment',
                array(
                    'main_assignment_id' => $newMainLocation->attribute( 'node_id' ),
                    'object_id' => $node->attribute( 'contentobject_id' ),
                    'main_assignment_parent_id' => $newMainLocation->attribute( 'parent_node_id' ) ),
                null,
                true
            );
        }
        else
        {
            eZContentOperationCollection::UpdateMainAssignment(
                $newMainLocation->attribute( 'node_id' ),
                $node->attribute( 'contentobject_id' ),
                $newMainLocation->attribute( 'parent_node_id' )
            );
        }
    }

    /**
     * Move $node to $destination
     *
     * @param eZContentObjectTreeNode $node
     * @param eZContentObjectTreeNode $destination
     *
     * @todo return false on errors
     * @todo add perms checking
     */
    static public function move( eZContentObjectTreeNode $node, eZContentObjectTreeNode $destination )
    {
        if ( self::isTriggersExecutionEnabled() && eZOperationHandler::operationIsAvailable( 'content_move' ) )
        {
            eZOperationHandler::execute(
                'content',
                'move',
                array(
                    'node_id' => $node->attribute( 'node_id' ),
                    'object_id' => $node->attribute( 'contentobject_id' ),
                    'new_parent_node_id' => $destination->attribute( 'node_id' ) ),
                null,
                true
            );
        }
        else
        {
            eZContentOperationCollection::moveNode(
                $node->attribute( 'node_id' ),
                $node->attribute( 'contentobject_id' ),
                $destination->attribute( 'node_id' )
            );
        }
    }

    /**
     * Remove a $node
     *
     * @param eZContentObjectTreeNode $node
     * @param bool $moveToTrash Whether to move the object to trash.
     * @todo add perms checking
     */
    static public function remove( eZContentObjectTreeNode $node, $moveToTrash )
    {
        $removeList = array( $node->attribute( 'node_id' ) );

        if ( self::isTriggersExecutionEnabled() && eZOperationHandler::operationIsAvailable( 'content_removelocation' ) )
        {
            eZOperationHandler::execute(
                'content',
                'removelocation',
                array( 'node_list' => $removeList ),
                null,
                true
            );
        }
        else
        {
            eZContentOperationCollection::removeNodes( $removeList );
        }
    }

    /**
     * Swap $node1 with $node2
     *
     * @param eZContentObjectTreeNode $node1
     * @param eZContentObjectTreeNode $node2
     */
    static public function swap( eZContentObjectTreeNode $node1, eZContentObjectTreeNode $node2 )
    {
        $nodeIds = array( $node1->attribute( "node_id" ), $node2->attribute( "node_id" ) );
        if ( self::isTriggersExecutionEnabled() && eZOperationHandler::operationIsAvailable( 'content_swap' ) )
        {
            eZOperationHandler::execute(
                'content',
                'swap',
                array(
                    'node_id' => $nodeIds[0],
                    'selected_node_id' => $nodeIds[1],
                    'node_id_list' => $nodeIds
                ),
                null,
                true
            );
        }
        else
        {
            eZContentOperationCollection::swapNode( $nodeIds[0], $nodeIds[1] );
        }
    }

    /**
     * Checks differences between the current node and another one
     * @return integer a bitmask of differences
     * /
    function checkDifferences( array $otherNode )
    {
        $out = 0;

        $selfNode = (array) $this;
        if ( $selfNode['hidden'] != $otherNode['hidden'] )
        {
            $out = $out & self::DIFF_VISIBILITY;
        }

        if ( $selfNode['sortField'] != $otherNode['sortField'] )
        {
            $out = $out & self::DIFF_SORTFIELD;
        }

        if ( $selfNode['sortField'] != $otherNode['sortOrder'] )
        {
            $out = $out & self::DIFF_SORTORDER;
        }

        $out = 0;
    }*/

    /**
     * Returns whether triggers execution is enabled.
     *
     * @return bool
     */
    static private function isTriggersExecutionEnabled()
    {
        return eZINI::instance( "contentstagingtarget.ini" )->variable( "GeneralSettings", "ExecuteTriggers" ) !== "disabled";
    }
}
