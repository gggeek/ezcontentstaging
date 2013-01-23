<?php
/**
 * Cli script that checks status of one or more target feeds
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://ez.no/Resources/Software/Licenses/eZ-Business-Use-License-Agreement-eZ-BUL-Version-2.1 eZ Business Use License Agreement eZ BUL Version 2.1
 *
 * @todo allow user to limit scan depth when scanning whole feeds
 * @todo internationalize output
 */

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => "Check sync status of all nodes in target feeds",
                                     'use-session'    => false,
                                     'use-modules'    => false,
                                     'use-extensions' => true ) );
$script->startup();
$options = $script->getOptions( "[targets:][sync][diff-only][pending-only]",
    "",
    array( 'targets' => 'list of target feeds (csv)',
           'diff-only' => 'only list nodes which differ',
           'pending-only' => 'only test nodes which have pending events (not recursive)',
           'sync' => 'when specified, synchronization events will be generated to bring target to parity with current installation' ) );
$script->initialize();

// love closures! php 5.3 only!
$displayFunction = function ( $problems ) use ( $options, $cli, $script )
{
    reset( $problems );
    $tmp = each( $problems );
    $nodeId = $tmp['key'];
    $problems = $tmp['value'];

    if ( $options['sync'] )
    {
        if ( $problems == 0 )
        {
            if ( !$options['diff-only'] )
            {
                $cli->output( "Node: $nodeId: OK - nothing to do" );
            }
        }
        else if ( $problems & eZBaseStagingTransport::DIFF_TRANSPORTERROR )
        {
            $cli->output( "Node: $nodeId: KO - error in retrieving remote status" );
        }
        else
        {
            $cli->error( "Node: $nodeId: KO - status $problems can not (yet) be handled" );
        }
    }
    else
    {
        if ( $problems != 0 || !$options['diff-only'] )
        {
            $out = "Node: $nodeId Status: $problems";
            if ( $script->verboseOutputLevel() && $problems != 0 )
            {
                $out .= " (" . implode( ', ', eZBaseStagingTransport::diffmask2array( $problems ) ) . ')';
            }
            $cli->output( $out );
        }
    }

};

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
    if ( $target )
    {
        $cli->output( "" );
        $cli->output( "Checking target: $targetId" );

        if ( $options['pending-only'] )
        {
            /// @todo use offset/limit here in case events list is huge
            $nodeIds = array();
            $events = eZContentStagingEvent::fetchList( $targetId );
            foreach( $events as $event )
            {
                $nodeIds = array_merge( $nodeIds, $event->getNodeIds() );
                $nodeIds = array_unique( $nodeIds );
            }
            $nodeCount = count( $nodeIds );
            $cli->output( "$nodeCount nodes to check in " . count( $events ) . " events" );
            $target->checkNodeList( $nodeIds, $displayFunction );
        }
        else
        {
            $nodeCount = $target->nodeCount();
            $cli->output( "$nodeCount nodes to check in " . count( $target->attribute( 'subtrees' ) ) . " subtrees" );
            $target->checkTarget( $displayFunction );
        }
    }
    else
    {
        $cli->output( "Target: $targetId not found. Can not check!" );
    }
}

$script->shutdown( 0 );
