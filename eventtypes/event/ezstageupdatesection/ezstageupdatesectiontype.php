<?php
/**
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @todo check: can we move this to after action instead of before?
 */

class eZStageUpdateSectionType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstageupdatesection';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'extension/ezcontentstaging/eventtypes', 'Stage section update' ) );
        $this->setTriggerTypes( array( 'content' => array( 'updatesection' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $nodeID = $parameters['node_id'];
        $sectionID = $parameters['selected_section_id'];

        // sanity checks

        $node = eZContentObjectTreeNode::fetch( $nodeID );
        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node ' . $nodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $object = $node->attribute( 'object' );
        $objectID = $object->attribute( 'id' );
        $objectNodes = eZContentStagingEvent::assignedNodeIds( $objectID );
        $newNodeData = array( "sectionID" => $sectionID, "objectRemoteID" => $object->attribute( 'remote_id' ) );
        //var_export( eZContentStagingTarget::fetchByNode( $node ) );
        //die();
        foreach( eZContentStagingTarget::fetchByNode( $node ) as $target_id => $target )
        {
            eZContentStagingEvent::addEvent(
                        $target_id,
                        $objectID,
                        eZContentStagingEvent::ACTION_UPDATESECTION,
                        $newNodeData,
                        $objectNodes
                    );
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageUpdateSectionType::WORKFLOW_TYPE_STRING, 'eZStageUpdateSectionType' );

?>