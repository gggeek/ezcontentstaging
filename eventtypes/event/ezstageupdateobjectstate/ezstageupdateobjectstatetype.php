<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZStageUpdateObjectStateType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageupdateobjectstate';

    public function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage update object state' ) );
        $this->setTriggerTypes( array( 'content' => array( 'updateobjectstate' => array( 'after' ) ) ) ); // ?
    }

    public function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $objectID = $parameters['object_id'];

        // sanity checks

        $object = eZContentObject::fetch( $objectID );
        if ( !is_object( $object ) )
        {
            eZDebug::writeError( 'Unable to fetch object ' . $objectID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }
        // format of what we get: an array with an int value (state) for every state group)
        // format of what we produce: an array with key = gourp identifier and value = state identifier
        $states = array();
        foreach ( $parameters['state_id_list'] as $stateId )
        {
            $state = eZContentObjectState::fetchById( $stateId );
            if ( !is_object( $state ) )
            {
                eZDebug::writeError( 'Unable to fetch object state ' . $stateId, __METHOD__ );
                continue;
            }
            $group = $state->attribute( 'group' );
            $states[$group->attribute( 'identifier')] = $state->attribute( 'identifier' );
        }

        $objectNodes = eZContentStagingEvent::assignedNodeIds( $objectID );
        $affectedObjectData = array(
            "objectRemoteID" => $object->attribute( 'remote_id' ),
            "stateList" => $states );
        foreach ( eZContentStagingTarget::fetchList() as $targetId => $target )
        {
            $affectedFeedNodes = array_keys( $target->includedNodesByPath( $objectNodes ) );
            if ( count( $affectedFeedNodes ) )
            {
                eZContentStagingEvent::addEvent(
                    $targetId,
                    $objectID,
                    eZContentStagingEvent::ACTION_UPDATEOBJECSTATE,
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

eZWorkflowEventType::registerEventType( eZStageUpdateObjectStateType::WORKFLOW_TYPE_STRING, 'eZStageUpdateObjectStateType' );
