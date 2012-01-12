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
    static function updateSort( eZContentObjectTreeNode $node, $sortField, $sortOrder )
    {
        $sortField = self::decodeSortField( $sortField );
        $sortOrder = self::decodeSortOrder( $sortOrder);

        if ( eZOperationHandler::operationIsAvailable( 'content_sort' ) )
        {
            $operationResult = eZOperationHandler::execute(
                'content',
                'sort',
                array( 'node_id' => $node->attribute( 'node_id' ),
                       'sorting_field' => $sortField,
                       'sorting_order' => $sortOrder ),
                null, true );
            /// @todo test if any errors occurred
            return 0;

        }
        else
        {
            $result = eZContentOperationCollection::changeSortOrder( $node->attribute( 'node_id' ), $sortField, $sortOrder );
            return ( $result['status'] == true ) ? 0 : -1;

            /* manual update
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
                   if ( $db->transactionCounter() )
                   {
                       $db->rollback();
                   }
                   return $e->getMessage();
               }*/
        }
    }

    /**
     * Update the priority of the $node
     *
     * @param eZContentObjectTreeNode $node
     * @param int $priority
     *
     * @todo shall we fix priorities of other nodes too if there is a conflict?
     */
    static function updatePriority( eZContentObjectTreeNode $node, $priority )
    {
        // note: the eZ API for this is mind-bending to say the least...
        $priorityArray = array( $priority );
        $priorityIDArray = array( $node->attribute( 'node_id') );

        if ( eZOperationHandler::operationIsAvailable( 'content_updatepriority' ) )
        {
            $operationResult = eZOperationHandler::execute(
                'content',
                'updatepriority',
                array(
                    'node_id' => $node->attribute( 'node_id' ),
                    'priority' => $priorityArray,
                    'priority_id' => $priorityIDArray ),
                null, true );
        }
        else
        {
            eZContentOperationCollection::updatePriority( $node->attribute( 'node_id'), $priorityArray, $priorityIDArray );

            /* manual update
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
                   if ( $db->transactionCounter() )
                   {
                       $db->rollback();
                   }
                   return $e->getMessage();
               }*/
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
    static function updateVisibility( eZContentObjectTreeNode $node, $hide )
    {
        if ( $node->attribute( 'is_hidden' ) != $hide )
        {
            if ( eZOperationHandler::operationIsAvailable( 'content_hide' ) )
            {
                $operationResult = eZOperationHandler::execute( 'content',
                                                                'hide',
                                                                 array( 'node_id' => $node->attribute( 'node_id' ) ),
                                                                 null, true );
            }
            else
            {
                eZContentOperationCollection::changeHideStatus( $node->attribute( 'node_id' ) );
                /* manual update
                if ( $hide )
                {
                    eZContentObjectTreeNode::hideSubTree( $node );
                }
                else
                {
                    eZContentObjectTreeNode::unhideSubTree( $node );
                }*/
            }
        }
    }

    static function updateMainLocation( eZContentObjectTreeNode $node, eZContentObjectTreeNode $newMainLocation )
    {
        $mainAssignmentID = $node->attribute( 'object' )->attribute( 'main_node' )->attribute( 'node_id' );
        if ( eZOperationHandler::operationIsAvailable( 'content_updatemainassignment' ) )
        {
            $operationResult = eZOperationHandler::execute(
                'content',
                'updatemainassignment',
                array(
                    'main_assignment_id' => $mainAssignmentID,
                    'object_id' => $node->attribute( 'contentobject_id' ),
                    'main_assignment_parent_id' => $newMainLocation->attribute( 'parent_node_id' ) ),
                null,
                true );
        }
        else
        {
            eZContentOperationCollection::UpdateMainAssignment(
                $mainAssignmentID,
                $node->attribute( 'contentobject_id' ),
                $newMainLocation->attribute( 'parent_node_id' ) );
        }
    }

    /**
    * @todo return false on errors
    * @todo add perms checking
    */
    static function move( eZContentObjectTreeNode $node, eZContentObjectTreeNode $dest )
    {
        if ( eZOperationHandler::operationIsAvailable( 'content_move' ) )
        {
            $operationResult = eZOperationHandler::execute(
                'content',
                'move',
                array( 'node_id' => $node->attribute( 'node_id' ),
                    'object_id' => $node->attribute( 'contentobject_id' ),
                    $dest->attribute( 'node_id' ) ),
                null,
                true );
        }
        else
        {
            eZContentOperationCollection::moveNode(
                $node->attribute( 'node_id' ),
                $node->attribute( 'contentobject_id' ),
                $dest->attribute( 'node_id' )
            );
        }
    }

    /**
    * @param bool $totrash
    * @todo add perms checking
    */
    static function remove( eZContentObjectTreeNode $node, $moveToTrash )
    {
        $removeList = array( $node->attribute( 'node_id' ) );

        if ( eZOperationHandler::operationIsAvailable( 'content_removelocation' ) )
        {
            $operationResult = eZOperationHandler::execute( 'content',
                                                            'removelocation', array( 'node_list' => $removeList ),
                                                            null,
                                                            true );
        }
        else
        {
            eZContentOperationCollection::removeNodes( $removeList );

            /* manual update
            eZContentObjectTreeNode::removeSubtrees(
                array( $node->attribute( 'node_id' ) ),
                $moveToTrash
            );*/
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
}
