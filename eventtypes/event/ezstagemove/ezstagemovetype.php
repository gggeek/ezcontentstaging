<?php

class eZStageMoveType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstagemove';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'extension/ezcontentstaging/eventtypes', 'Stage move' ) );
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

        $nodePath = array( $node->attribute( 'path_string' ) );
        $newParentNodePath = $newParentNode( $newParentNode->attribute( 'path_string' ) );
        $affectedNodes = array( $nodeID );
        $movedObjectData = array( 'parentNodeID' => $newParentNodeID, 'parentNodeRemoteID' => $newParentNode->attribute( 'remote_id' ) );
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
                        $movedObjectData,
                        $affectedNodes
                    );
                }
                else
                {
                    // record a remove-node event to this target

                    /// @todo ...
                }
            }
            else
            {
                if ( $hasNewParentNode )
                {
                    // record a create-node event to this target

                    /// @todo ...
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

?>