<?php

class eZStageUpdateMainAssignmentType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageupdatemainassignment';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'extension/ezcontentstaging/eventtypes', 'Stage update main assignment' ) );
        $this->setTriggerTypes( array( 'content' => array( 'updatemainassignment' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );

        $mainNodeID = $parameters['main_assignment_id'];
        $objectID = $parameters['object_id'];

        // sanity checks

        $mainNode = eZContentObjectTreeNode::fetch( $mainNodeID );
        if ( !is_object( $mainNode ) )
        {
            eZDebug::writeError( 'Unable to fetch node ' . $mainNodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $object = eZContentObject::fetch( $objectID );
        if ( !is_object( $object ) )
        {
            eZDebug::writeError( 'Unable to fetch object ' . $objectID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        // we need to propagate this event to feeds that include either old or new main node
        $prevMainNode = $object->attribute( 'main_node' );
        $affectedNodes = array(
            $prevMainNode->attribute( 'node_id' ) => $prevMainNode->attribute( 'path_string' ),
            $mainNode->attribute( 'node_id' ) => $mainNode->attribute( 'path_string' )
        );

        $objectNodes = eZContentStagingEvent::assignedNodeIds( $objectID );
        $affectedObjectData = array( "objectRemoteID" => $object->attribute( 'remote_id' ), "..." => '' );

        foreach ( eZContentStagingTarget::fetchList() as $target_id => $target )
        {
            $affectedFeedNodes = array_keys( $target->includedNodesByPath( $affectedNodes ) );
            if ( count( $affectedFeedNodes ) )
            {
                /// @todo what if new main node is not in target feed?
                eZContentStagingEvent::addEvent(
                    $target_id,
                    $objectID,
                    eZContentStagingEvent::ACTION_UPDATEMAINASSIGNMENT,
                    $affectedObjectData,
                    // We always mark every node as affected, even though
                    // in practice a given node might not be part of any feed.
                    // This way we insure that when looking at the node via ezwt
                    // it is marked as for-sync even though to be synced are in
                    // reality the other nodes of the same object
                    array_keys( $objectNodes )
                );
            }
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageUpdateMainAssignmentType::WORKFLOW_TYPE_STRING, 'eZStageUpdateMainAssignmentType' );

?>