<?php
/**
 * Cli script that unlocks events in "syncing status"
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2013 eZ Systems AS. All rights reserved.
 *
 * @todo internationalize output
 */

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => "Unlocks events which are left in 'syncing' status",
                                     'use-session'    => false,
                                     'use-modules'    => false,
                                     'use-extensions' => true ) );
$script->startup();
$options = $script->getOptions( "[targets:][list][delay:]",
    "",
    array( 'targets' => 'list of target feeds (csv)',
           'delay' => 'Safety measure: only unlock events which have been in sync status for longer than <delay> hours. Defaults to 24',
           'list' => 'only display the events found without touching them'
    )
);
$script->initialize();

$targets = $options['targets'];
if ( $targets == '' )
{
    $ini = eZINI::instance( 'contentstagingsource.ini' );
    $targets = $ini->variable( 'GeneralSettings', 'TargetList' );
}
else
{
    $targets = explode( ',', $targets );
}

// we allow user to specify 0 delay
if ( $options['delay'] === null )
{
    $delay = 24;
}
else
{
    $delay = (int)$options['delay'];
}
if ( !$options['list'] )
{
    $cli->output( "Unlocking events which have been in sync status for more than $delay hours" );
}
$delay = $delay * 60 * 60;
$now = time();

foreach ( $targets as $targetId )
{
    $target = eZContentStagingTarget::fetch( $targetId );
    $locale = eZLocale::instance();
    if ( $target )
    {
        $cli->output( "" );
        $cli->output( "Checking target: $targetId" );

        $events = eZContentStagingEvent::fetchList( $targetId, true, null, null, null, eZContentStagingEvent::STATUS_SYNCING );
        $cli->output( "Found  " . count( $events ) . " events in in sync status" );
        foreach( $events as $event )
        {
            if ( $options['list'] )
            {
                $cli->output( "Found event " . $event->attribute( 'id' ) . ", has been in sync status since " . $locale->formatShortDateTime( $event->attribute( 'sync_begin_date' ) ) );
                if ( $script->verboseOutputLevel() )
                {
                    $cli->output( "    To sync: " . $event->attribute( 'to_sync_string' ) . ", Object: " . $event->attribute( 'object_id' ) . ", Node(s): " . implode( ',', $event->attribute( 'node_ids' ) ) . ", created: " . $locale->formatShortDateTime( $event->attribute( 'modified' ) ) );
                }
            }
            else
            {
                if ( $now - $event->attribute( 'sync_begin_date' ) > $delay )
                {
                    $cli->output( "Unlocking event " . $event->attribute( 'id' ) . ", has been in sync status since " . $locale->formatShortDateTime( $event->attribute( 'sync_begin_date' ) ) );
                    $event->abortSync();
            }
                else
                {
                    $cli->output( "NOT unlocking event " . $event->attribute( 'id' ) . ", has been in sync status only since " . $locale->formatShortDateTime( $event->attribute( 'sync_begin_date' ) ) );
                }
            }
        }
    }
    else
    {
        $cli->output( "Target: $targetId not found. Can not check!" );
    }
}

$script->shutdown( 0 );
