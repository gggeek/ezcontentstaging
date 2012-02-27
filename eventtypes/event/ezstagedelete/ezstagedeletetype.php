<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZStageDeleteType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstagedelete';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'ezcontentstaging/eventtypes', 'Stage delete' ) );
        $this->setTriggerTypes( array( 'content' => array( 'delete' => array( 'before' ) ) ) );
    }

    /**
     * @todo test when this event gets triggered and when removelocation gets triggered instead
     * @todo test that it is correct: we get a node_id, we do not check for notification all feeds that relate
     *       to all nodes of the object, but only to current node. We assume thus
     *       a single node is left when this is triggered?
     */
    function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $trash = $parameters['move_to_trash'];

        if ( isset( $parameters['node_id_list'] ) )
        {
            $nodeList = eZContentObjectTreeNode::fetch( $parameters['node_id_list'] );
            if ( $nodeList instanceof eZContentObjectTreeNode )
            {
                $nodeList = array( $nodeList );
            }

            /// @todo !important check that count( $nodeList ) == count( $parameters['node_id_list'] )
        }
        else
        {
            /// @todo !important there seems to be no trace in kernel code of invocation of this event using $parameters['node_list'] instead of $parameters['node_id_list']. Remove?
            $nodeList = $parameters['node_list'];
        }

        foreach ( $nodeList as $node )
        {
            if ( !$node || !is_object( $node ) )
            {
                eZDebug::writeError( 'Element in node list is not an object', __METHOD__ );
                continue;
            }

            $object = $node->attribute( 'object' );
            $objectID = $object->attribute( 'id' );
            //$nodeRemoteID = $node->attribute( 'remote_id' );
            $objectNodes = eZContentStagingEvent::assignedNodeIds( $objectID );
            /// @bug we should not store object name in a version which is localized with current language...
            /// @todo decide which data we save inside the 'object' array
            $deletObjectData = array(
                "objectRemoteID" => $object->attribute( 'remote_id' ),
                "trash" => $trash,
                "object" => array(
                    "name" => $object->attribute( 'name' ),
                    "class_name" => $object->attribute( 'class_name' ),
                    "published" => $object->attribute( 'published' ),
                    "modified" => $object->attribute( 'modified' ),
                    "owner_id" => $object->attribute( 'owner_id' ) ) );
            foreach ( eZContentStagingTarget::fetchByNode( $node ) as $target_id => $target )
            {
                eZContentStagingEvent::addEvent(
                    $target_id,
                    $objectID,
                    eZContentStagingEvent::ACTION_DELETE,
                    $deletObjectData,
                    /// @todo decide:
                    // which nodes to mrk as affected? In theory there should
                    // be none left after the delete. But we run this trigger before the
                    // actual action...
                    array_keys( $objectNodes )
                    );
            }
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageDeleteType::WORKFLOW_TYPE_STRING, 'eZStageDeleteType' );
