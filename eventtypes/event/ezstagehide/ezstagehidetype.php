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
 * @todo check of ot can be moved to after action instead of before
 */

class eZStageHideType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezstagehide';

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'extension/ezcontentstaging/eventtypes', 'Stage (un)hide' ) );
        $this->setTriggerTypes( array( 'content' => array( 'hide' => array( 'before' ) ) ) );
    }

    function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
        $nodeID = $parameters['node_id'];

        // sanity checks

        $node = eZContentObjectTreeNode::fetch( $nodeID );
        if ( !is_object( $node ) )
        {
            eZDebug::writeError( 'Unable to fetch node ' . $nodeID, __METHOD__ );
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $objectId = $node->attribute( 'contentobject_id' );
        $nodeRemoteID = $node->attribute( 'remote_id' );
        $hidden = $node->attribute( 'is_hidden' );
        foreach( eZContentStagingTarget::fetchByNode( $node ) as $target_id => $target )
        {
            eZContentStagingEvent::addEvent(
                $target_id,
                $objectId,
                eZContentStagingEvent::ACTION_HIDEUNHIDE,
                array( 'nodeID' => $nodeID, 'nodeRemoteID' => $nodeRemoteID, 'hide' => $hidden ),
                array( $nodeID )
                );
        }
        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZStageHideType::WORKFLOW_TYPE_STRING, 'eZStageHideType' );

?>