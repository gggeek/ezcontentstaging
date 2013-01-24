<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @todo check of ot can be moved to after action
 */

class eZStageRemoveLocationType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageremovelocation';

    public function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage remove location' ) );
        $this->setTriggerTypes( array( 'content' => array( 'removelocation' => array( 'before' ) ) ) );
    }

    /**
     * NB: definition of this trigger has changed slightly from 4.1.4 to 4.5:
     *     parameters node_id, object_id and move_to_trash have been removed from call in content/action
     */
    public function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $removedNodeList = $parameters['node_list'];
        $trash = isset( $parameters['move_to_trash'] ) ? $parameters['move_to_trash'] : true;

        // sanity checks

        if ( empty( $removedNodeList ) )
        {
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $removedNodeRemoteIDList = array();
        foreach ( $removedNodeList as $i => $removedNode )
        {
            if ( is_numeric( $removedNode ) )
            {
                $removedNode = eZContentObjectTreeNode::fetch( $removedNode );
            }

            if ( $removedNode instanceof eZContentObjectTreeNode )
            {
               if ( $i == 0 )
               {
                   $objectId = $removedNode->attribute( 'contentobject_id' );
               }
               $removedNodeRemoteIDList[$removedNode->attribute( 'path_string' )] = array(
                   "nodeID" => $removedNode->attribute( 'node_id' ),
                   "nodeRemoteID" => $removedNode->attribute( 'remote_id' ),
                   "trash" => $trash
               );
            }
        }

        if ( empty( $removedNodeRemoteIDList ) )
        {
            eZDebug::writeError( 'Unable to fetch removed nodes for nodeID ' . $nodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }
        // set this event to be shown on all remaining nodes
        $affectedNodes = array_diff( array_keys( eZContentStagingEvent::assignedNodeIds( $objectId ) ), $removedNodeList );
        foreach ( eZContentStagingTarget::fetchList() as $targetId => $target )
        {
            foreach ( $removedNodeRemoteIDList as $removedNodePathString => $removedNodeData )
            {
                if ( $target->includesNodeByPath( $removedNodePathString ) )
                {
                    eZContentStagingEvent::addEvent(
                        $targetId,
                        $objectId,
                        eZContentStagingEvent::ACTION_REMOVELOCATION,
                        $removedNodeData,
                        /// @todo verify: shall we always mark all nodes as affected? maybe we should limit this more
                        $affectedNodes
                    );
                }
            }
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageRemoveLocationType::WORKFLOW_TYPE_STRING, 'eZStageRemoveLocationType' );
