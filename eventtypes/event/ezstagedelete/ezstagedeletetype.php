<?php

class eZStageDeleteType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstagedelete';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezi18n( 'extension/ezcontentstaging/eventtypes', 'Stage delete' ) );
        $this->setTriggerTypes( array( 'content' => array( 'delete' => array( 'before' )  ) ) );
    }

    function execute( $process, $event )
    {
        /*$parameters = $process->attribute( 'parameter_list' );

        if ( isset( $parameters['node_id_list'] ) )
        {
            $nodeList = eZContentObjectTreeNode::fetch( $parameters['node_id_list'] );
            if ( $nodeList instanceof eZContentObjectTreeNode )
                $nodeList = array( $nodeList );
        }
        else
        {
            $nodeList = $parameters['node_list'];
        }

        foreach ( $nodeList as $node )
        {
            if ( !$node || !is_object( $node ) )
            {
                eZDebug::writeError( 'Element in node list is not an object.', 'eZStageDeleteType::execute' );
                continue;
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
                    'action' => eZSyndicationNodeActionLog::ACTION_DELETE ) );

                $log->store();
            }
        }*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageDeleteType::WORKFLOW_TYPE_STRING, 'eZStageDeleteType' );

?>