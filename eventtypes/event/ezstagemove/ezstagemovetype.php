<?php

class eZStageMoveType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstagemove';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'extension/ezcontentstaging/eventtypes', 'Stage move' ) );
        $this->setTriggerTypes( array( 'content' => array( 'move' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        /*$parameters = $process->attribute( 'parameter_list' );

	    $node_id = $parameters['node_id'];
	    $object_id = $parameters['object_id'];
	    $new_parent_node_id = $parameters['new_parent_node_id'];

        $nodeObject = eZContentObjectTreeNode::fetch($node_id);
        $parentNodeObject = eZContentObjectTreeNode::fetch( $new_parent_node_id );

        if ( !is_object( $nodeObject ) OR  !is_object( $parentNodeObject ) )
        {
            eZDebug::writeError( 'Unable to fetch node for nodeID ' . $node_id . " OR Parent nodeID " . $new_parent_node_id, 'eZStageMoveType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $optionList = array( 'parent_remote_id' => $parentNodeObject->attribute( 'remote_id' ) );

        $feedSourceIDList = eZSyndicationNodeActionLog::feedSourcesByNode( $nodeObject );
        $nodeRemoteID = $nodeObject->attribute( 'remote_id' );
        $time = time();

        foreach ( $feedSourceIDList as $feedSourceID )
        {
            $log = new eZSyndicationNodeActionLog( array(
                'source_id' => $feedSourceID,
                'node_remote_id' => $nodeRemoteID,
                'timestamp' => $time,
                'action' => eZSyndicationNodeActionLog::ACTION_MOVE,
		        'options' => serialize( $optionList ) ) );
            $log->store();
        }*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageMoveType::WORKFLOW_TYPE_STRING, 'eZStageMoveType' );

?>