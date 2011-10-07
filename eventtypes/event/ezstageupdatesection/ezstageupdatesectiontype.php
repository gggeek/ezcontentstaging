<?php

class eZStageUpdateSectionType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageupdatesection';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'extension/ezcontentstaging/eventtypes', 'Stage section update' ) );
        $this->setTriggerTypes( array( 'content' => array( 'updatesection' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );

        $nodeID = $parameters['node_id'];

        $node = eZContentObjectTreeNode::fetch( $nodeID );

        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node ' . $nodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $object = $node->attribute( 'object' );
        $objectID = $object->attribute( 'id' );
        $objectNodes = eZContentStagingItem::assignedNodeIds( $objectID );
        foreach( eZContentStagingTarget::fetchByNode( $node ) as $target_id => $target )
        {
            eZContentStagingItem::addEvent(
                        $target_id,
                        $objectID,
                        eZContentStagingItemEvent::ACTION_UPDATESECTION,
                        $newNodeData,
                        $objectNodes
                    );
        }

        /*$nodeRemoteID = $node->attribute( 'remote_id' );
        $time = time();

        foreach ( $feedSourceIDList as $feedSourceID )
        {
            $log = new eZSyndicationNodeActionLog( array(
                'source_id' => $feedSourceID,
                'node_remote_id' => $nodeRemoteID,
                'timestamp' => $time,
                'action' => eZSyndicationNodeActionLog::ACTION_UPDATE_SECTION,
                'options' => serialize( array( 'selected_section_id' => $parameters['selected_section_id'] ) ) ) );

            $log->store();
        }*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageUpdateSectionType::WORKFLOW_TYPE_STRING, 'eZStageUpdateSectionType' );

?>