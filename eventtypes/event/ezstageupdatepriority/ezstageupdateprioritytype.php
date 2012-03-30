<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
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
        for ( $i = 0; $i < count( $priorityArray ); $i++ )
        {
            $childNodePriority = (int) $priorityArray[$i];
            $childNodeID = (int) $priorityIDArray[$i];
            if ( $childNodeID == 0 || !( $childNode = eZContentObjectTreeNode::fetch( $childNodeID ) ) )
            {
                eZDebug::writeError( 'Unable to fetch child node ' . $nodeID . ' to set priority value to it', __METHOD__ );
                continue;
            }
            $childNodeRemoteID = $childNode->attribute( 'remote_id' );
            $priorityArrayList[] = array( 'nodeID' => $childNodeID,
                                          'nodeRemoteID' => $childNodeRemoteID,
                                          'priority' => $childNodePriority );
        }

        $objectId = $node->attribute( 'contentobject_id' );
        /// @todo !important we could avoid to encode into event the parent node id+remote_id for a small space saving
        $affectedNodes = array( $nodeID );
        $prioritizedNodesData = array(
            'nodeID' => $nodeID,
            'nodeRemoteID' => $node->attribute( 'remote_id' ),
            'priorities' => $priorityArrayList );
        foreach ( eZContentStagingTarget::fetchByNode( $node ) as $target_id => $target )
        {
            eZContentStagingEvent::addEvent(
                $target_id,
                $objectId,
                eZContentStagingEvent::ACTION_UPDATEPRIORITY,
                $prioritizedNodesData,
                $affectedNodes );
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageUpdatePriorityType::WORKFLOW_TYPE_STRING, 'eZStageUpdatePriorityType' );
