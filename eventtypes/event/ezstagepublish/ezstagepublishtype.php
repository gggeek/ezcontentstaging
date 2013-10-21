<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZStagePublishType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstagepublish';

    public function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage content publish' ) );
        $this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'after' ) ) ) );
    }

    public function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $objectID = $parameters['object_id'];
        $versionID = $parameters['version'];

        // sanity checks

        $object = eZContentObject::fetch( $objectID );
        if ( !$object )
        {
            eZDebug::writeError( "Unable to fecth object $objectID", __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }
        $version = $object->version( $versionID );
        if ( !$version )
        {
            eZDebug::writeError( "No version $versionID for object $objectID", __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }
        $parentNodeId = $version->attribute( 'main_parent_node_id' );
        $parentNode = eZContentObjectTreeNode::fetch( $parentNodeId );
        if ( !$parentNode )
        {
            eZDebug::writeError( 'Unable to fetch parent node ' . $parentNodeId,  __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        /// @todo shall we check if status is "published" here?
        ///       test if eg. after collab. refusal we pass through here...

        // if this is a 1st version, we need to identify parent node and store its ids too

        $objectNodes = eZContentStagingEvent::assignedNodeIds( $objectID );
        $affectedObjectData = array(
            'version' => $versionID,
            'objectRemoteID' => $object->attribute( 'remote_id' ),
            'parentNodeID' => $parentNode->attribute( 'node_id' ),
            'parentNodeRemoteID' => $parentNode->attribute( 'remote_id' ) );

        // try to save new node id + remote id too
        $node = $object->attribute( 'main_node' );
        if ( $node )
        {
            $affectedObjectData['nodeID'] = $node->attribute( 'node_id' );
            $affectedObjectData['nodeRemoteID'] = $node->attribute( 'remote_id' );
        }

        // Work around the fact that restoration from trash does not have a trigger,
        // but it generates a publication event.
        // We check current module/view to sniff that.
        // The @ is ugly but prevents a useless warning from missing static declarations
        if ( @eZModule::currentModule() == 'content' && @eZModule::currentView() == 'restore' )
        {
            $eventType = eZContentStagingEvent::ACTION_RESTOREFROMTRASH;
            // affects all existing languages
            $initialLanguageID = null;
        }
        else
        {
            $eventType = eZContentStagingEvent::ACTION_PUBLISH;
            $initialLanguageID = $version->attribute( 'initial_language_id' );
            $affectedObjectData['locale'] = eZContentLanguage::fetch( $initialLanguageID )->attribute( 'locale' );
        }

        /** @var eZContentStagingTarget $target */
        foreach ( eZContentStagingTarget::fetchList() as $targetId => $target )
        {
            $affectedFeedNodes = array_keys( $target->includedNodesByPath( $objectNodes ) );
            if ( !empty( $affectedFeedNodes ) )
            {
                eZContentStagingEvent::addEvent(
                    $targetId,
                    $objectID,
                    $eventType,
                    $affectedObjectData,
                    array_keys( $objectNodes ),
                    $initialLanguageID
                );
            }
        }

        /*
        if ( $version->attribute( 'status' ) != eZContentObjectVersion::STATUS_PUBLISHED )
        {
            eZDebug::writeNotice( "Object not published(status: " . $version->attribute( 'status' ) . "), no syndication needed.", 'eZStagePublishType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $parentNodeId = $version->attribute( 'main_parent_node_id' );
        $parentNode = eZContentObjectTreeNode::fetch( $parentNodeId );

        if ( !is_object( $parentNode ) )
        {
            eZDebug::writeError( 'Unable to fetch parent node with ID ' . $parentNodeId, 'eZStagePublishType::execute' );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $assignedNodeRemoteIds = array();
        $assignedNodes = $object->assignedNodes();
        $mainNode = $object->attribute( 'main_node' );
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
                'options' => serialize( array( 'object_remote_id' => $object->attribute( 'remote_id' ),
                                               'object_modified' => $object->attribute( 'modified' ),
                                               'version_created' => $version->attribute( 'created' ),
                                               'version_modified' => $version->attribute( 'modified' ),
                                               'version_language_mask' => $version->attribute( 'language_mask' ),
                                               'assigned_nodes' => $assignedNodeRemoteIds,
                                               'main_node' => $mainNode->attribute( 'remote_id' )
                                       ) ) ) );

            $log->store();
        }
        eZDebug::writeNotice( "Done, thanks for watching..", 'eZStagePublishType::execute' );*/

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStagePublishType::WORKFLOW_TYPE_STRING, 'eZStagePublishType' );
