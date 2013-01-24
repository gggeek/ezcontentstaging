<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @todo check: can we move this to after action instead of before?
 */

class eZStageUpdateSectionType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageupdatesection';

    public function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage update section' ) );
        $this->setTriggerTypes( array( 'content' => array( 'updatesection' => array( 'before' ) ) ) );
    }

    public function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $nodeID = $parameters['node_id'];

        // sanity checks

        $node = eZContentObjectTreeNode::fetch( $nodeID );
        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node ' . $nodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $object = $node->attribute( 'object' );
        $objectID = $object->attribute( 'id' );
        $objectNodes = eZContentStagingEvent::assignedNodeIds( $objectID );
        $affectedObjectData = array( "sectionID" => $parameters['selected_section_id'], "objectRemoteID" => $object->attribute( 'remote_id' ) );
        foreach ( eZContentStagingTarget::fetchList() as $targetId => $target )
        {
            $affectedFeedNodes = array_keys( $target->includedNodesByPath( $objectNodes ) );
            if ( !empty( $affectedFeedNodes ) )
            {
                eZContentStagingEvent::addEvent(
                    $targetId,
                    $objectID,
                    eZContentStagingEvent::ACTION_UPDATESECTION,
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

eZWorkflowEventType::registerEventType( eZStageUpdateSectionType::WORKFLOW_TYPE_STRING, 'eZStageUpdateSectionType' );
