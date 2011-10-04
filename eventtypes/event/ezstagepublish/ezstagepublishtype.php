<?php

class eZStagePublishType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstagepublish';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'extension/ezcontentstaging/eventtypes', 'Stage content publish' ) );
        $this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'after' ) ) ) );
    }

    function execute( $process, $event )
    {
        /*$parameters = $process->attribute( 'parameter_list' );
        $objectID = $parameters['object_id'];
        $object = eZContentObject::fetch( $objectID );

        if ( !$object )
        {
            eZDebug::writeError( "No object with ID $objectID", 'eZStagePublishType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $versionID = $parameters['version'];
        $version = $object->version( $versionID );

        if ( !$version )
        {
            eZDebug::writeError( "No version $versionID for object with ID $objectID", 'eZStagePublishType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        if ( $version->attribute('status') != eZContentObjectVersion::STATUS_PUBLISHED )
        {
            eZDebug::writeNotice( "Object not published(status: " . $version->attribute('status') . "), no syndication needed.", 'eZStagePublishType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $parentNodeId = $version->attribute('main_parent_node_id');
        $parentNode = eZContentObjectTreeNode::fetch( $parentNodeId );

        if ( !is_object( $parentNode ) )
        {
            eZDebug::writeError( 'Unable to fetch parent node with ID ' . $parentNodeId, 'eZStagePublishType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $assignedNodeRemoteIds = array();
        $assignedNodes = $object->assignedNodes();
        $mainNode = $object->attribute('main_node');
        foreach ( $assignedNodes as $assignedNode )
        {
            $assignedNodeRemoteIds[] = $assignedNode->attribute( 'remote_id' );
        }

        $feedSourceIDList = eZSyndicationNodeActionLog::feedSourcesByNode( $mainNode );

        if ( !$feedSourceIDList )
        {
            eZDebug::writeNotice( "Object not part of any export feed, no syndication needed.", 'eZStagePublishType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $parentNodeRemoteID = $parentNode->attribute( 'remote_id' );
        $time = time();

        foreach ( $feedSourceIDList as $feedSourceID )
        {
            $log = new eZSyndicationNodeActionLog( array(
                'source_id' => $feedSourceID,
                'node_remote_id' => $parentNodeRemoteID,
                'timestamp' => $time,
                'action' => eZSyndicationNodeActionLog::ACTION_PUBLISH,
                'options' => serialize( array( 'object_remote_id' => $object->attribute('remote_id'),
                                               'object_modified' => $object->attribute('modified'),
                                               'version_created' => $version->attribute('created'),
                                               'version_modified' => $version->attribute('modified'),
                                               'version_language_mask' => $version->attribute('language_mask'),
                                               'assigned_nodes' => $assignedNodeRemoteIds,
                                               'main_node' => $mainNode->attribute('remote_id')
                                       ) ) ) );

            $log->store();
        }
        eZDebug::writeNotice( "Done, thanks for watching..", 'eZStagePublishType::execute' );*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStagePublishType::WORKFLOW_TYPE_STRING, 'eZStagePublishType' );

?>