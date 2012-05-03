<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZStageSwapType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageswap';

    public function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage node swap' ) );
        $this->setTriggerTypes( array( 'content' => array( 'swap' => array( 'before' ) ) ) );
    }

    public function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $nodeID = $parameters['node_id'];
        $node = eZContentObjectTreeNode::fetch( $nodeID );

        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node with ID ' . $nodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $selectedNodeID = $parameters['selected_node_id'];
        $selectedNode = eZContentObjectTreeNode::fetch( $selectedNodeID );

        if ( !is_object( $selectedNode ) )
        {
            eZDebug::writeError( 'Unable to fetch selected node with ID ' . $selectedNodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $objectID = $node->attribute( 'contentobject_id' );
        $swapData = array(
            'nodeID1' => $nodeID,
            'nodeRemoteID1' => $node->attribute( 'remote_id' ),
            'nodeID2' => $selectedNodeID,
            'nodeRemoteID2' => $selectedNode->attribute( 'remote_id' ),
        );
        $affectedNodes = array( $nodeID, $selectedNodeID );

        foreach ( array_keys( eZContentStagingTarget::fetchList() ) as $targetId )
        {
            eZContentStagingEvent::addEvent(
                $targetId,
                $objectID,
                eZContentStagingEvent::ACTION_SWAP,
                $swapData,
                $affectedNodes
            );
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageSwapType::WORKFLOW_TYPE_STRING, 'eZStageSwapType' );
