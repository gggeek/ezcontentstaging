<?php
/**
* View used to check one feed
* Supports pagination
*
* @todo add functionality to sync complete feed (all events), not just X events (either here or in feeds view)
*
* @package ezcontentstaging
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*
*/

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();
$targetId = $Params['target_id'];

$configurationErrors = array();
$connectionErrors = array();
if ( $targetId !== null )
{
    /// @todo check that target exists (either here or in tpl code)
    $configurationErrors = eZContentStagingTarget::checkConfiguration( $targetId );
    $feed = eZContentStagingTarget::fetch( $targetId );
    if ( $feed )
    {
        $connectionErrors = $feed->checkConnection();
    }
}

$tpl->setVariable( 'target_id', $targetId );
$tpl->setVariable( 'configurationErrors', $configurationErrors );
$tpl->setVariable( 'connectionErrors', $connectionErrors );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:contentstaging/checkfeed.tpl' );
$Result['path'] = array( array( 'text' => ezpI18n::tr( 'ezcontentstaging', 'Content synchronization' ),
                                'url' => 'contentstaging/feeds' ),
                         array(
                                'text' => ezpI18n::tr( 'ezcontentstaging', "Feed: $targetId" ),
                                'url' => 'contentstaging/feed/' . $targetId ) );

?>