<?php

class eZStageAddLocationType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageaddlocation';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezi18n( 'extension/ezcontentstaging/eventtypes', 'Stage Add Location' ) );
        $this->setTriggerTypes( array( 'content' => array( 'addlocation' => array( 'after' ) ) ) );
    }

    function execute( $process, $event )
    {
        /*$parameters = $process->attribute( 'parameter_list' );
        $nodeID = $parameters['node_id'];
        $selectNodeIDArray = $parameters['select_node_id_array'];

        $node = eZContentObjectTreeNode::fetch($nodeID);

        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node for nodeID ' . $nodeID, 'eZStageAddLocationType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        if( count( $selectNodeIDArray) <= 0 )
    	{
    	    return eZWorkflowType::STATUS_ACCEPTED;
    	}

        $contentObjectID = $node->attribute( 'contentobject_id' );

        $selectedNodeRemoteIDArray = array();
	    $db = eZDB::instance();
        $db->begin();

        $newParentNodes = eZContentObjectTreeNode::fetch( $selectNodeIDArray );
        if ( $newParentNodes instanceof eZContentObjectTreeNode )
            $newParentNodes = array( $newParentNodes );

        foreach( $newParentNodes as $parentNode )
    	{
    	    if ( eZSyndicationNodeActionLog::feedSourcesByNode( $parentNode, true ) )
    	    {
        	    $sqlchildNode = "SELECT remote_id FROM ezcontentobject_tree where contentobject_id = '" . $contentObjectID . "' AND parent_node_id = '" . $parentNode->attribute('node_id') . "'";
                $child_remote_id = $db->arrayQuery( $sqlchildNode  );
                $selectedNodeRemoteIDArray[] = array( 'parent_remote_id' => $parentNode->attribute('remote_id'),
    	                                              'child_remote_id' => $child_remote_id['0']['remote_id'] );
    	    }
    	}
        $db->commit();

        if( count( $selectedNodeRemoteIDArray ) <= 0 )
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
                'action' => eZSyndicationNodeActionLog::ACTION_ADD_LOCATION,
                'options' => serialize( $selectedNodeRemoteIDArray ) ) );
            $log->store();
        }*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageAddLocationType::WORKFLOW_TYPE_STRING, 'eZStageAddLocationType' );

?>