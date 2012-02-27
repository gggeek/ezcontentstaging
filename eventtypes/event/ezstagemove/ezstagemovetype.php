<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZStageMoveType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstagemove';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage move' ) );
        $this->setTriggerTypes( array( 'content' => array( 'move' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $nodeID = $parameters['node_id'];
        $newParentNodeID = $parameters['new_parent_node_id'];
        $objectID = $parameters['object_id'];

        // sanity checks

        $node = eZContentObjectTreeNode::fetch( $nodeID );
        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node ' . $nodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $newParentNode = eZContentObjectTreeNode::fetch( $newParentNodeID );
        if ( !is_object( $newParentNode ) )
        {
            eZDebug::writeError( 'Unable to fetch node ' . $newParentNodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $object = $node->attribute( 'object' );
        $nodePath = array( $node->attribute( 'path_string' ) );
        $newParentNodePath = $newParentNode->attribute( 'path_string' );
        $affectedNodes = array( $nodeID );
        $movedNodeData = array(
            'nodeID' => $nodeID,
            'nodeRemoteID' => $node->attribute( 'remote_id' ),
            'parentNodeID' => $newParentNodeID,
            'parentNodeRemoteID' => $newParentNode->attribute( 'remote_id' ),
            'objectRemoteID' => $object->attribute( 'remote_id' ) );
        $currentVersion = $object->attribute( 'current_version' );
        $initialLanguageID = $object->attribute( 'initial_language_id' );
        $initialLanguage = eZContentLanguage::fetch( $initialLanguageID );
        foreach ( eZContentStagingTarget::fetchList() as $target_id => $target )
        {
            $hasNode = $target->includesNodeByPath( $nodePath );
            $hasNewParentNode =  $target->includesNodeByPath( $newParentNodePath );
            if ( $hasNode )
            {
                if ( $hasNewParentNode )
                {
                    // record a move-node event to this target
                    eZContentStagingEvent::addEvent(
                        $target_id,
                        $objectID,
                        eZContentStagingEvent::ACTION_MOVE,
                        $movedNodeData,
                        $affectedNodes
                    );
                }
                else
                {
                    // record a remove-node event to this target
                    eZContentStagingEvent::addEvent(
                        $target_id,
                        $objectID,
                        eZContentStagingEvent::ACTION_REMOVELOCATION,
                        array_merge( $movedNodeData, array( 'trash' => false ) ),
                        $affectedNodes
                    );
                }
            }
            else
            {
                if ( $hasNewParentNode )
                {
                    // record a create-node event to this target
                    /// @todo in fact we should import all versions and languages...
                    eZContentStagingEvent::addEvent(
                        $target_id,
                        $objectID,
                        eZContentStagingEvent::ACTION_PUBLISH,
                        array_merge( $movedNodeData, array(
                            'version' => $currentVersion,
                            'locale' => $initialLanguage->attribute( 'locale' )
                        ) ),
                        $affectedNodes,
                        $initialLanguageID
                    );
                }
                else
                {
                    // nothing to see here, move along
                }
            }
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageMoveType::WORKFLOW_TYPE_STRING, 'eZStageMoveType' );
