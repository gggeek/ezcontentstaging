<?php
/**
*
* @deprecated
*
* View used to sync one node
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

$syncErrors = array();
$syncResults = array();

$events = array();
if ( $Params['event_ids'] != null )
{
    foreach( explode( ',', $Params['event_ids'] ) as $eventId )
    {
        // check that sync item exists
        $event = eZContentStagingEvent::fetch( $eventId );
        if ( $event instanceof eZContentStagingEvent )
        {
            $events[$event->attribute( 'id' )] = $event;
        }
        else
        {
            eZDebug::writeWarning( "Invalid event id received for syncing: $eventId", 'contentstaging/sync' );
        }
    }
}

if ( count( $events ) )
{
    ksort( $events );
    foreach( $events as $id => $event )
    {
        /// @todo check that current user can sync - with limitations - this event

        /// go
        if ( ( $result = $event->syncEvent() ) !== 0 )
        {
            $syncErrors[] = "Error $result while synchronizing object " .   $event->attribute( 'object_id' ) . " to target " . $event->attribute( 'target_id' ) . " [Event $id]\n";
        }
        else
        {
            $syncResults[] = "";
        }
    }
}
else
{
    $syncErrors[] = "No object(s) to be synchronized";
}

$tpl = eZTemplate::factory();
$tpl->setVariable( 'sync_events', $events );
$tpl->setVariable( 'sync_errors', $syncErrors );
$tpl->setVariable( 'sync_results', $syncResults );

$Result['content'] = $tpl->fetch( 'design:contentstaging/sync.tpl' );

$Result['path'] = array( array( 'text' => ezpI18n::tr( 'staging', 'Content synchronization' ),
								'url' => 'contentstaging/feeds' ) );
/*if ( $target_id == null )
{
    $Result['path'][] = array( 'text' => ezpI18n::tr( 'staging', 'All feeds' ),
                               'url' => 'contentstaging/feed' );
}
else
{
    /// @todo use the name of the feed, not its id
    $Result['path'][] = array( 'text' => ezpI18n::tr( 'staging', "Feed: $target_id" ),
                               'url' => "contentstaging/feed/$target_id" );
}*/
$Result['path'][] = array( 'text' => ezpI18n::tr( 'staging', 'Synchronise object' ),
                           'url' => false );

?>