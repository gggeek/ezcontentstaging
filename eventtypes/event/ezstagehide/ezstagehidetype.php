<?php

class eZStageHideType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstagehide';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezi18n( 'extension/ezcontentstaging/eventtypes', 'Stage (un)hide' ) );
        $this->setTriggerTypes( array( 'content' => array( 'hide' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        /*$parameters = $process->attribute( 'parameter_list' );

        $nodeID = $parameters['node_id'];

        $node = eZContentObjectTreeNode::fetch( $nodeID );

        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node with ID ' . $nodeID, 'eZStageHideType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $feedSourceIDList = eZSyndicationNodeActionLog::feedSourcesByNode( $node );

        $nodeRemoteID = $node->attribute( 'remote_id' );
        $time = time();

        $action = $node->attribute( 'is_hidden' ) ? eZSyndicationNodeActionLog::ACTION_UNHIDE : eZSyndicationNodeActionLog::ACTION_HIDE;

        foreach ( $feedSourceIDList as $feedSourceID )
        {
            $log = new eZSyndicationNodeActionLog( array(
                'source_id' => $feedSourceID,
                'node_remote_id' => $nodeRemoteID,
                'timestamp' => $time,
                'action' => $action ) );

            $log->store();
        }*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageHideType::WORKFLOW_TYPE_STRING, 'eZStageHideType' );

?>