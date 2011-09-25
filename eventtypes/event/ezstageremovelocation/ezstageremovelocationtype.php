<?php

class eZStageRemoveLocationType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageremovelocation';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezi18n( 'extension/ezcontentstaging/eventtypes', 'Stage Remove Location' ) );
        $this->setTriggerTypes( array( 'content' => array( 'removelocation' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        /*$parameters = $process->attribute( 'parameter_list' );
        $nodeID = $parameters['node_id'];
        $removedNodeList = $parameters['node_list'];

        $node = eZContentObjectTreeNode::fetch($nodeID);

       if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node for nodeID ' . $nodeID, 'eZStageRemoveLocationType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        if( count( $removedNodeList ) <= 0 )
    	{
    	    return eZWorkflowType::STATUS_ACCEPTED;
    	}

        $removedNodeRemoteIDList = array();
        foreach( $removedNodeList as $removedNode )
        {
            if ( is_numeric( $removedNode ) )
            {
                $removedNode = eZContentObjectTreeNode::fetch( $removedNode );
            }

            if ( $removedNode instanceof eZContentObjectTreeNode
              && eZSyndicationNodeActionLog::feedSourcesByNode( $removedNode, true, false ) )
            {
               $removedNodeRemoteIDList[] = $removedNode->attribute( 'remote_id' );
            }
        }

        if( count( $removedNodeRemoteIDList ) <= 0 )
        {
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $feedSourceIDList = eZSyndicationNodeActionLog::feedSourcesByNode( $node );
        $nodeRemoteID = $node->attribute( 'remote_id' );
        $time = time();

        foreach ( $feedSourceIDList as $feedSourceID )
        {
            $log = new eZSyndicationNodeActionLog( array(
                'source_id' => $feedSourceID,
                'node_remote_id' => $nodeRemoteID,
                'timestamp' => $time,
                'action' => eZSyndicationNodeActionLog::ACTION_REMOVE_LOCATION,
                'options' => serialize( $removedNodeRemoteIDList ) ) );
            $log->store();
        }*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageRemoveLocationType::WORKFLOW_TYPE_STRING, 'eZStageRemoveLocationType' );

?>