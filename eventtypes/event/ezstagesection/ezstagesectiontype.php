<?php

class eZStageSectionType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstagesection';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezi18n( 'extension/ezcontentstaging/eventtypes', 'Stage section update' ) );
        $this->setTriggerTypes( array( 'content' => array( 'updatesection' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        /*$parameters = $process->attribute( 'parameter_list' );

        $nodeID = $parameters['node_id'];

        $node = eZContentObjectTreeNode::fetch( $nodeID );

        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node with ID ' . $nodeID, 'eZStageSectionType::execute' );
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
                'action' => eZSyndicationNodeActionLog::ACTION_UPDATE_SECTION,
                'options' => serialize( array( 'selected_section_id' => $parameters['selected_section_id'] ) ) ) );

            $log->store();
        }*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageSectionType::WORKFLOW_TYPE_STRING, 'eZStageSectionType' );

?>