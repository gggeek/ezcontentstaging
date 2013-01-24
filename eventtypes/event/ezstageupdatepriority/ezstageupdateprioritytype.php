<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZStageUpdatePriorityType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageupdatepriority';

    public function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage update priority' ) );
        $this->setTriggerTypes( array( 'content' => array( 'updatepriority' => array( 'before' ) ) ) );
    }

    /// @todo shall we show this event as relating to children nodes instead of parent node?
    public function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );

        $nodeID = $parameters['node_id'];
        $priorityArray = $parameters['priority'];
        $priorityIDArray = $parameters['priority_id'];

        // sanity checks

        $node = eZContentObjectTreeNode::fetch( $nodeID );
        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node ' . $nodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $priorityArrayList = array();
        /// @todo !important usage of a foreach is ok here?
        for ( $i = 0, $priorityArrayCount = count( $priorityArray ); $i < $priorityArrayCount; $i++ )
        {
            $childNodeID = (int) $priorityIDArray[$i];
            if ( $childNodeID == 0 || !( $childNode = eZContentObjectTreeNode::fetch( $childNodeID ) ) )
            {
                eZDebug::writeError( 'Unable to fetch child node ' . $nodeID . ' to set priority value to it', __METHOD__ );
                continue;
            }
            $priorityArrayList[] = array(
                'nodeID' => $childNodeID,
                'nodeRemoteID' => $childNode->attribute( 'remote_id' ),
                'priority' => (int)$priorityArray[$i]
            );
        }

        $objectId = $node->attribute( 'contentobject_id' );
        /// @todo !important we could avoid to encode into event the parent node id+remote_id for a small space saving
        $affectedNodes = array( $nodeID );
        $prioritizedNodesData = array(
            'nodeID' => $nodeID,
            'nodeRemoteID' => $node->attribute( 'remote_id' ),
            'priorities' => $priorityArrayList
        );
        foreach ( eZContentStagingTarget::fetchByNode( $node ) as $targetId => $target )
        {
            eZContentStagingEvent::addEvent(
                $targetId,
                $objectId,
                eZContentStagingEvent::ACTION_UPDATEPRIORITY,
                $prioritizedNodesData,
                $affectedNodes );
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageUpdatePriorityType::WORKFLOW_TYPE_STRING, 'eZStageUpdatePriorityType' );
