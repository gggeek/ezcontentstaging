<?php

class eZStageSwapType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageswap';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezi18n( 'extension/ezcontentstaging/eventtypes', 'Stage node swap' ) );
        $this->setTriggerTypes( array( 'content' => array( 'swap' => array( 'before' ) ) ) );
    }

    /*
     \reimp
    */
    function execute( $process, $event )
    {
        /*$parameters = $process->attribute( 'parameter_list' );

        $nodeID = $parameters['node_id'];

        $node = eZContentObjectTreeNode::fetch( $nodeID );

        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node with ID ' . $nodeID, 'eZStageSwapType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $selectedNodeID = $parameters['selected_node_id'];

        $selectedNode = eZContentObjectTreeNode::fetch( $selectedNodeID );

        if ( !is_object( $selectedNode ) )
        {
            eZDebug::writeError( 'Unable to fetch selected node with ID ' . $selectedNodeID, 'eZStageSwapType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $feedSourceIDList = eZSyndicationNodeActionLog::feedSourcesByNode( $node );

        $nodeRemoteID = $node->attribute( 'remote_id' );
        $selectedRemoteID = $selectedNode->attribute( 'remote_id' );
        $time = time();

        foreach ( $feedSourceIDList as $feedSourceID )
        {
            $log = new eZSyndicationNodeActionLog( array(
                'source_id' => $feedSourceID,
                'node_remote_id' => $nodeRemoteID,
                'timestamp' => $time,
                'action' => eZSyndicationNodeActionLog::ACTION_SWAP_NODES,
                'options' => serialize( array( 'selected_node_remote_id' => $selectedRemoteID ) ) ) );

            $log->store();
        }*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageSwapType::WORKFLOW_TYPE_STRING, 'eZStageSwapType' );

?>