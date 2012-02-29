<?php
/**
*  Cronjob used (optionally) to sync all pending events
*
* @package ezcontentstaging
*
* @author
* @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

/// @todo add parsing of a cli option to get target host name

foreach( eZContentStagingTarget::fetchList() as $id => $target )
{
    if ( !$isQuiet )
        $cli->output( "Syncing target: $id" );

    /// @todo make this scale better by fetching in loops instead of single pass
    $events = eZContentStagingEvent::fetchList( $id, true, null, null, null, eZContentStagingEvent::STATUS_TOSYNC );
    $eventCount = count( $events );
    if ( !$isQuiet )
        $cli->output( "Events to synchronize: $eventCount" );
    $out = eZContentStagingEvent::syncEvents( $events );
    if ( !$isQuiet )
    {
        $ok = $ko = 0;
        foreach( $out as $id => $resp )
        {
            if ( $resp === 0 )
            {
                $ok++;
            }
            else
            {
                $ko++;
            }
        }
        $cli->output( "Events synchronized: $ok, failed: $ko" );
    }
}

?>
