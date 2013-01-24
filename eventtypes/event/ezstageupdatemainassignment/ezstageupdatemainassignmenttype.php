<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZStageUpdateMainAssignmentType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageupdatemainassignment';

    public function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage update main assignment' ) );
        $this->setTriggerTypes( array( 'content' => array( 'updatemainassignment' => array( 'before' ) ) ) );
    }

    public function execute( $process, $event )
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
        $affectedObjectData = array(
            "objectRemoteID" => $object->attribute( 'remote_id' ),
            "nodeID" => $mainNodeID,
            "nodeRemoteID" => $mainNode->attribute( 'remote_id' )
        );
        foreach ( eZContentStagingTarget::fetchList() as $targetId => $target )
        {
            $affectedFeedNodes = array_keys( $target->includedNodesByPath( $affectedNodes ) );
            if ( !empty( $affectedFeedNodes ) )
            {
                /// @todo what if new main node is not in target feed?
                eZContentStagingEvent::addEvent(
                    $targetId,
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
