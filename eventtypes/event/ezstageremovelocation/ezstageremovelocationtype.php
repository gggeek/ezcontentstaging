<?php

class eZStageRemoveLocationType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageremovelocation';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'extension/ezcontentstaging/eventtypes', 'Stage Remove Location' ) );
        $this->setTriggerTypes( array( 'content' => array( 'removelocation' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $removedNodeList = $parameters['node_list'];

        // sanity checks

        if( count( $removedNodeList ) == 0 )
        {
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        // unluckily we miss current object id from operation, so we get it
        // later on from 1st removed node
        /*$node = eZContentObjectTreeNode::fetch( $nodeID );
        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node for nodeID ' . $nodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }
        $object = $node->attribute( 'object' );*/

        $removedNodeRemoteIDList = array();
        foreach( $removedNodeList as $i => $removedNode )
        {
            if ( is_numeric( $removedNode ) )
            {
                $removedNode = eZContentObjectTreeNode::fetch( $removedNode );
            }

            if ( $removedNode instanceof eZContentObjectTreeNode )
            {
               if ( $i == 0 )
               {
                   $objectId = $removedNode->attribute( 'contentobject_id' );
               }
               $removedNodeRemoteIDList[$removedNode->attribute( 'path_string' )] = $removedNode->attribute( 'remote_id' );
            }
        }

        if ( count( $removedNodeRemoteIDList ) == 0 )
        {
            eZDebug::writeError( 'Unable to fetch removed nodes for nodeID ' . $nodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }
        foreach ( eZContentStagingTarget::fetchList() as $target_id => $target )
        {
            foreach( $removedNodeRemoteIDList as $removedNodePathString => $removedNodeRemoteId )
            {
                if ( $target->includesNodeByPath( $removedNodePathString ) )
                {
                    eZContentStagingItem::addEvent(
                        $target_id,
                        $objectId,
                        eZContentStagingItemEvent::ACTION_REMOVELOCATION,
                        array( 'remoteId' => $removedNodeRemoteId )
                    );
                }
            }
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageRemoveLocationType::WORKFLOW_TYPE_STRING, 'eZStageRemoveLocationType' );

?>