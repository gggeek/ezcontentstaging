<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZStageUpdateInitialLanguageType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageupdateinitiallanguage';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage update intial language' ) );
        $this->setTriggerTypes( array( 'content' => array( 'updateinitiallanguage' => array( 'before' ) ) ) );
    }

    /**
     * An event that is set purely to the object, ie. it affects all its nodes
     */
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

        $objectID = $object->attribute( 'id' );
        $objectNodes = eZContentStagingEvent::assignedNodeIds( $objectID );
        $affectedObjectData = array( "initialLanguage" => $parameters['new_initial_language_id'], "objectRemoteID" => $object->attribute( 'remote_id' ) );
        foreach ( eZContentStagingTarget::fetchList() as $target_id => $target )
        {
            $affectedFeedNodes = array_keys( $target->includedNodesByPath( $objectNodes ) );
            if ( count( $affectedFeedNodes ) )
            {
                eZContentStagingEvent::addEvent(
                    $target_id,
                    $objectID,
                    eZContentStagingEvent::ACTION_UPDATEINITIALLANGUAGE,
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

eZWorkflowEventType::registerEventType( eZStageUpdateInitialLanguageType::WORKFLOW_TYPE_STRING, 'eZStageUpdateInitialLanguageType' );
