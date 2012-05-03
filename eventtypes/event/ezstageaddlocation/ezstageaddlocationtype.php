<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZStageAddLocationType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageaddlocation';

    public function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage add location' ) );
        $this->setTriggerTypes( array( 'content' => array( 'addlocation' => array( 'after' ) ) ) );
    }

    public function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $selectNodeIDArray = $parameters['select_node_id_array'];

        // sanity checks

        if ( empty( $selectNodeIDArray ) )
        {
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $objectID = $parameters['object_id'];
        $object = eZContentObject::fetch( $objectID );
        if ( !is_object( $object ) )
        {
            eZDebug::writeError( 'Unable to fetch object ' . $objectID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        // fetch list of new parent nodes to make sure they exist AND to get their remote IDs
        $newParentNodes = eZContentObjectTreeNode::fetch( $selectNodeIDArray );
        if ( $newParentNodes instanceof eZContentObjectTreeNode )
        {
            $newParentNodes = array( $newParentNodes );
        }
        else if ( empty( $newParentNodes ) )
        {
            eZDebug::writeError( 'Unable to fetch new parent nodes for object ' . $objectID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        // build data for all newly created nodes
        $newNodesData = array();
        // be fast with single query instead of going the object way
        /// @todo use eZPO fetchObject function instead of direct eZDB for better abstraction
        ///       maybe these are already in node cache anyway?
        $db = eZDB::instance();
        foreach ( $newParentNodes as $newParentNode )
        {
            $data = $db->arrayQuery(
                "SELECT node_id, remote_id, priority, sort_field, sort_order, path_string " .
                "FROM ezcontentobject_tree " .
                "WHERE contentobject_id = '$objectID' AND parent_node_id  = " . $newParentNode->attribute( 'node_id' )
            );
            /// @todo test for errors
            $data = $data[0];
            $newNodesData[$data['path_string']] = array(
                'nodeID' => $data['node_id'],
                'nodeRemoteID' => $data['remote_id'],
                'objectRemoteID' => $object->attribute( 'remote_id' ),
                'parentNodeID' => $newParentNode->attribute( 'node_id' ),
                'parentNodeRemoteID' => $newParentNode->attribute( 'remote_id' ),
                'priority' => $data['priority'],
                'sortField' => $data['sort_field'],
                'sortOrder' => $data['sort_order']
            );
        }

        // finally add, for every target feed, all new nodes that fall within it
        // Question: shall we show this event on every node, or only on new nodes ???
        $affectedNodes = array_keys( eZContentStagingEvent::assignedNodeIds( $objectID ) );
        foreach ( eZContentStagingTarget::fetchList() as $targetId => $target )
        {
            foreach ( $newNodesData as $newNodePathString => $newNodeData )
            {
                if ( !$target->includesNodeByPath( $newNodePathString ) )
                    continue;

                eZContentStagingEvent::addEvent(
                    $targetId,
                    $object->attribute( 'id' ),
                    eZContentStagingEvent::ACTION_ADDLOCATION,
                    $newNodeData,
                    $affectedNodes
                );
            }
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageAddLocationType::WORKFLOW_TYPE_STRING, 'eZStageAddLocationType' );
