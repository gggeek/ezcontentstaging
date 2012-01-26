<?php
/**
* View used to check one node status vs. one target server
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

$nodeId = $Params['node_id'];
$targetId = $Params['target_id'];

$checkErrors = '';
$checkResults = -1;
$target = null;
$node = eZContentObjectTreeNode::fetch( $nodeId );
if ( $node )
{
    $target = eZContentStagingTarget::fetch( $targetId );
    if ( $target )
    {
        $checkResults = $target->checkNode( $node, false );
        $checkResults = eZBaseStagingTransport::diffmask2array( $checkResults[$nodeId] );
    }
    else
    {
        /// @todo make translatable
        $checkErrors = "Invalid target id: $targetId";
    }
}
else
{
    /// @todo make translatable
    $checkErrors = "Invalid node id: $nodeId";
}


$tpl = eZTemplate::factory();
$tpl->setVariable( 'current_node', $node );
$tpl->setVariable( 'feed', $target );
$tpl->setVariable( 'check_errors', $checkErrors );
$tpl->setVariable( 'check_results', $checkResults );

$Result['content'] = $tpl->fetch( 'design:contentstaging/checknode.tpl' );

$Result['path'] = array( array( 'text' => ezpI18n::tr( 'staging', 'Content synchronization' ),
								'url' => 'contentstaging/feeds' ),
                         array( 'text' => ezpI18n::tr( 'staging', 'Node status check' ),
                                'url' => false ) );

?>