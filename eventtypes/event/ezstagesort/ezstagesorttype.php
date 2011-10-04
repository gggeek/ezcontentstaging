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
        /*$parameters = $process->attribute( 'parameter_list' );

        $nodeID = $parameters['node_id'];

        $node = eZContentObjectTreeNode::fetch($nodeID);

        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node for nodeID ' . $nodeID, 'eZStageSortType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $feedSourceIDList = eZSyndicationNodeActionLog::feedSourcesByNode( $node );
        $nodeRemoteID = $node->attribute( 'remote_id' );
        $time = time();
        $action = eZSyndicationNodeActionLog::ACTION_SORT;
        $options = array( 'sorting_field' => $parameters['sorting_field'], 'sort_order' => $parameters['sorting_order'] );

        foreach ( $feedSourceIDList as $feedSourceID )
        {
            $log = new eZSyndicationNodeActionLog( array(
                'source_id' => $feedSourceID,
                'node_remote_id' => $nodeRemoteID,
                'timestamp' => $time,
                'action' => $action,
                'options' => serialize( $options ) ) );
            $log->store();
        }*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageSortType::WORKFLOW_TYPE_STRING, 'eZStageSortType' );

?>