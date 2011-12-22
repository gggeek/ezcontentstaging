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

$http = eZHTTPTool::instance();

$tpl = eZTemplate::factory();
/*$tpl->setVariable( 'current_node', $currentNode );
$tpl->setVariable( 'sync_related_objects', $relatedObjectNeedingSync );
$tpl->setVariable( 'target_id', $targetId );
$tpl->setVariable( 'current_node_events', $current_node_events );
$tpl->setVariable( 'related_node_events', $related_node_events_list );
$tpl->setVariable( 'sync_errors', $syncErrors );
$tpl->setVariable( 'sync_results', $syncResults );*/

$Result['content'] = $tpl->fetch( 'design:contentstaging/checknode.tpl' );

$Result['path'] = array( array( 'text' => ezpI18n::tr( 'staging', 'Content synchronization' ),
								'url' => 'contentstaging/feeds' ),
                         array( 'text' => ezpI18n::tr( 'staging', 'Node status check' ),
                                'url' => false ) );

?>