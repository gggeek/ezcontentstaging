<?php

class eZStageRemoveTranslationType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageremovetranslation';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'extension/ezcontentstaging/eventtypes', 'Stage translation remove' ) );
        $this->setTriggerTypes( array( 'content' => array( 'removetranslation' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        /*$parameters = $process->attribute( 'parameter_list' );

        if ( isset( $parameters['node_id'] ) )
        {
            $errorText = ' node ID ' . $parameters['node_id'];
            $node = eZContentObjectTreeNode::fetch( $parameters['node_id'] );
        }
        else
        {
            // In case node id is not supplied, use object id (this depends on patch level)
            $errorText = ' content object ID ' . $parameters['object_id'];
            $nodeList = eZContentObjectTreeNode::fetchByContentObjectID( $parameters['object_id'] );
            $node = $nodeList[0];
        }

        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node for' . $errorText, 'eZStageRemoveTranslationType::execute' );
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
                'action' => eZSyndicationNodeActionLog::ACTION_REMOVE_TRANSLATION,
                'options' => serialize( array( 'language_id_list' => $parameters['language_id_list'] ) ) ) );

            $log->store();
        }*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageRemoveTranslationType::WORKFLOW_TYPE_STRING, 'eZStageRemoveTranslationType' );

?>