<?php

class eZStageUpdatePriorityType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageupdatepriority';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezi18n( 'extension/ezcontentstaging/eventtypes', 'Stage Update Priority' ) );
        $this->setTriggerTypes( array( 'content' => array( 'updatepriority' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        /*$parameters = $process->attribute( 'parameter_list' );

        $nodeID = $parameters['node_id'];
        $priorityArray = $parameters['priority'];
        $priorityIDArray = $parameters['priority_id'];

        $node = eZContentObjectTreeNode::fetch($nodeID);

        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node for nodeID ' . $nodeID, 'eZStageUpdatePriorityType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $priorityArrayList = array();

        for ( $i=0; $i<count( $priorityArray );$i++ )
        {
            $priority = (int) $priorityArray[$i];
            $node_id = (int) $priorityIDArray[$i];
	    $nodeObject = eZContentObjectTreeNode::fetch( $node_id );
	    $remote_id = $nodeObject->attribute( 'remote_id' );
            $priorityArrayList[] = array( 'remote_id' => $remote_id,
                                          'priority' => $priority );
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
                'action' => eZSyndicationNodeActionLog::ACTION_UPDATE_PRIORITY,
                'options' => serialize($priorityArrayList ) ) );
            $log->store();
        }*/
        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageUpdatePriorityType::WORKFLOW_TYPE_STRING, 'eZStageUpdatePriorityType' );

?>