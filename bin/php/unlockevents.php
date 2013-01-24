<?php
/**
 * Cli script that unlocks events in "syncing status"
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2013 eZ Systems AS. All rights reserved.
 *
 * @todo add a "minimum grace period" parameter
 * @todo internationalize output
 */

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => "Unlocks events which are left in 'syncing' status",
                                     'use-session'    => false,
                                     'use-modules'    => false,
                                     'use-extensions' => true ) );
$script->startup();
$options = $script->getOptions( "[targets:][list]",
    "",
    array( 'targets' => 'list of target feeds (csv)',
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
                $cli->output( "Unlocking event " . $event->attribute( 'id' ) . ", has been in sync status since " . $locale->formatShortDateTime( $event->attribute( 'sync_begin_date' ) ) );
                $event->abortSync();
            }
        }
    }
    else
    {
        $cli->output( "Target: $targetId not found. Can not check!" );
    }
}

$script->shutdown( 0 );
