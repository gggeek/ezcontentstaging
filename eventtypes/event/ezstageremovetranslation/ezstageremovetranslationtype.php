<?php

class eZStageRemoveTranslationType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageremovetranslation';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage translation remove' ) );
        $this->setTriggerTypes( array( 'content' => array( 'removetranslation' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
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

        $objectNodes = eZContentStagingEvent::assignedNodeIds( $objectID );
        $affectedObjectData = array( "objectRemoteID" => $object->attribute( 'remote_id' ), "translations" => $parameters['language_id_list'] );

        foreach ( eZContentStagingTarget::fetchList() as $target_id => $target )
        {
            $affectedFeedNodes = array_keys( $target->includedNodesByPath( $objectNodes ) );
            if ( count( $affectedFeedNodes ) )
            {
                eZContentStagingEvent::addEvent(
                    $target_id,
                    $objectID,
                    eZContentStagingEvent::ACTION_REMOVETRANSLATION,
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

eZWorkflowEventType::registerEventType( eZStageRemoveTranslationType::WORKFLOW_TYPE_STRING, 'eZStageRemoveTranslationType' );

?>