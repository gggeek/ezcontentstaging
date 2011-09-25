<?php

class eZStageUpdateMainAssignmentType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageupdatemainassignment';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezi18n( 'extension/ezcontentstaging/eventtypes', 'Stage update main assignment' ) );
        $this->setTriggerTypes( array( 'content' => array( 'updatemainassignment' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        /*$parameters = $process->attribute( 'parameter_list' );

        $MainAssignmentID = $parameters['main_assignment_id'];
        $ObjectID = $parameters['object_id'];

        $MainAssignment = eZContentObjectTreeNode::fetch( $MainAssignmentID );
        $Object = eZContentObject::fetch( $ObjectID );
        if ( !is_object( $MainAssignment ) )
        {
            eZDebug::writeError( 'Unable to fetch node with ID ' . $MainAssignmentID, 'eZStageUpdateMainAssignmentType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        if ( !is_object( $Object ) )
        {
            eZDebug::writeError( 'Unable to fetch object with ID ' . $ObjectID, 'eZStageUpdateMainAssignmentType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $feedSourceIDList = eZSyndicationNodeActionLog::feedSourcesByNode( $MainAssignment );

        $nodeAssignmentArray = array( 'object_remote_id' => $Object->attribute( 'remote_id' ),
                                      'main_assignment_parent_remote_id' => $MainAssignment->attribute( 'remote_id' ) );
        $time = time();
        foreach ( $feedSourceIDList as $feedSourceID )
        {
            $log = new eZSyndicationNodeActionLog( array(
                'source_id' => $feedSourceID,
                'node_remote_id' => $MainAssignment->attribute( 'remote_id' ),
                'timestamp' => $time,
                'action' => eZSyndicationNodeActionLog::ACTION_UPDATE_MAIN_ASSIGNMENT,
                'options' => serialize( $nodeAssignmentArray ) ) );
            $log->store();
        }*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageUpdateMainAssignmentType::WORKFLOW_TYPE_STRING, 'eZStageUpdateMainAssignmentType' );

?>