<?php
/**
* View used to display one feed (or all of them together), and sync it
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

if ( $module->isCurrentAction( 'SyncEvents' ) )
{
    // test if current user has access to contentstaging/sync, as access to this view is only limited by 'view'
    $user = eZUser::currentUser();
    $hasAccess = $user->hasAccessTo( 'contentstaging', 'sync' );
    if ( $hasAccess['accessWord'] === 'no' )
    {
        return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
    }

    $actionErrors = array();
    $actionResults = array();
    if ( $http->hasPostVariable( 'syncArray' ) && is_array( $http->postVariable( 'syncArray' ) ) )
    {
        $tosync = array();
        foreach ( $http->postVariable( 'syncArray' ) as $eventId )
        {
            $event = eZContentStagingEvent::fetch( $eventId );
            /// @todo with finer grained perms, we should check user can sync these items, one by one
            if ( $event instanceof eZContentStagingEvent )
            {
                $tosync[$event->attribute( 'id' )] = $event;
            }
            else
            {
                eZDebug::writeError( "Invalid event id received for syncing: $eventId", 'contentstaging/feed' );
            }
        }
        // we sync by sorting based on event IDs to keep proper history
        ksort( $tosync );
        $out = eZContentStagingEvent::syncEvents( $tosync );
        /// @todo apply i18n to messages
        foreach( $out as $id => $resultCode )
        {
            $event = $tosync[$id];
            if ( $resultCode !== 0 )
            {
                $actionErrors[] = " Object " . $event->attribute( 'object_id' ) . " to be synchronised to feed " . $event->attribute( 'target_id' ) . ": failure ($resultCode) [Event $id]";
            }
            else
            {
                $actionResults[] = "Object " . $event->attribute( 'object_id' ) . " succesfully synchronised to feed " . $event->attribute( 'target_id' ) . " [Event $id]";
            }
        }

    }
    else
    {
        eZDebug::writeError( "No list of events to be syncronised received. Pen testing? tsk tsk tsk", __METHOD__ );
        $actionErrors[] = ezpI18n::tr( 'ezcontentstaging', "No object to synchronize" );
    }
    /// @todo decide format for these 2 variables: let translation happen here or in tpl?
    $tpl->setVariable( 'action_errors', $actionErrors );
    $tpl->setVariable( 'action_results', $actionResults );
    $tpl->setVariable( 'action', 'synchronisation' );

} // end of 'doing sync' action
else if (   $module->isCurrentAction( 'RemoveEvents' ) )
{
    // test if current user has access to contentstaging/manage, as access to this view is only limited by 'view'
    $user = eZUser::currentUser();
    $hasAccess = $user->hasAccessTo( 'contentstaging', 'manage' );
    if ( $hasAccess['accessWord'] === 'no' )
    {
        return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
    }

    $actionErrors = array();
    $actionResults = array();
    if ( $http->hasPostVariable( 'syncArray' ) && is_array( $http->postVariable( 'syncArray' ) ) )
    {
        $toremove = array();
        foreach ( $http->postVariable( 'syncArray' ) as $eventId )
        {
            $event = eZContentStagingEvent::fetch( $eventId );
            /// @todo with finer grained perms, we should check user can sync these items, one by one
            if ( $event instanceof eZContentStagingEvent )
            {
                $toremove[] = $event->attribute( 'id' );
            }
            else
            {
                eZDebug::writeError( "Invalid event id received for removal: $eventId", 'contentstaging/feed' );
            }
        }
        /// @todo we are actually faking the number of deleted events...
        $out = eZContentStagingEvent::removeEvents( $toremove );
        /// @todo apply i18n to messages
        if ( $out === false )
        {
            $actionErrors[] = "Error: events not removed (" . implode( ', ', $toremove ) . ')';
        }
        else
        {
            $actionResults[] = "$out events removed (" . implode( ', ', $toremove ) . ')';
        }

    }
    else
    {
        eZDebug::writeError( "No list of events to be removed received. Pen testing? tsk tsk tsk", __METHOD__ );
        /// @todo apply i18n to message
        $actionErrors[] = "No object to remove...";
    }
    /// @todo decide format for these 2 variables: let translation happen here or in tpl?
    $tpl->setVariable( 'action_errors', $actionErrors );
    $tpl->setVariable( 'action_results', $actionResults );
    $tpl->setVariable( 'action', 'removal' );
}

if ( $targetId !== null )
{
    /// @todo check that target exists (either here or in tpl code)
}

/// @todo !important fetch list of items to be displayed here, not purely in template

$tpl->setVariable( 'target_id', $targetId );
$tpl->setVariable( 'view_parameters', array( 'offset' => (int)$Params['Offset'] ) );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:contentstaging/feed.tpl' );
$Result['path'] = array( array( 'text' => ezpI18n::tr( 'ezcontentstaging', 'Content synchronization' ),
                                'url' => 'contentstaging/feeds' ) );
if ( $targetId == null )
{
    $Result['path'][] = array( 'text' => ezpI18n::tr( 'ezcontentstaging', 'All feeds' ),
                               'url' => false );
}
else
{
    /// @todo use the name of the feed, not its id
    $Result['path'][] = array( 'text' => ezpI18n::tr( 'ezcontentstaging', "Feed: $targetId" ),
                               'url' => 'contentstaging/feed/' . $targetId );
}

?>