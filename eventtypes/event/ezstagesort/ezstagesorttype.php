<?php

class eZStageSortType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstagesort';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'extension/ezcontentstaging/eventtypes', 'Stage sort' ) );
        $this->setTriggerTypes( array( 'content' => array( 'sort' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
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

        $objectID = $node->attribute( 'contentobject_id' );
        $sortedNodeData = array(
                    'nodeID' => $nodeID,
                    'nodeRemoteID' => $node->attribute( 'remote_id' ),
                    'sortField' => $parameters['sorting_field'],
                    'sortOrder' => $parameters['sorting_order'] );
        $affectedNodes = array( $nodeID );
        foreach( eZContentStagingTarget::fetchByNode( $node ) as $target_id => $target )
        {
            eZContentStagingEvent::addEvent(
                $target_id,
                $objectID,
                eZContentStagingEvent::ACTION_SORT,
                $sortedNodeData,
                $affeceteNodes );
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageSortType::WORKFLOW_TYPE_STRING, 'eZStageSortType' );

?>