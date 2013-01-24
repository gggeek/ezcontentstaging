<?php
/**
 * Cli script that checks sync status of one node for all taregt feeds it is part of
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @todo internationalize output
 */

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => "Check sync status of a single node in target feeds",
                                     'use-session'    => false,
                                     'use-modules'    => false,
                                     'use-extensions' => true ) );
$script->startup();
$options = $script->getOptions( "[sync]",
    "[nodeId]",
    array( 'sync' => 'when specified, synchronization events will be generated to bring target to parity with current installation' ) );
$script->initialize();

if ( count( $options['arguments'] ) != 1 )
{
    $script->shutdown( 1, 'wrong argument count' );
}
$nodeId = $options['arguments'][0];

$node = eZContentObjectTreeNode::fetch( $nodeId );
if ( !$node )
{
    $cli->output( "Node $nodeId not found in content" );
    $script->shutdown( 0 );
}

$targets = eZContentStagingTarget::fetchByNode( $node );
if ( !count( $targets ) )
{
    $cli->output( "Node $nodeId not found in any feed" );
    $script->shutdown( 0 );
}
$cli->output( "Node $nodeId found in feeds: " . implode( ', ', array_keys( $targets ) ) );

$events = eZContentStagingEvent::fetchByNodeGroupedByTarget( $nodeId );

foreach ( $targets as $targetId => $target )
{
    $cli->output( "" );
    $problems = $target->checkNode( $node, false );
    $out = "Target: $targetId, Status: " . $problems[$nodeId];
    if ( $script->verboseOutputLevel() && $problems[$nodeId] != 0 )
    {
        $out .= ' (' . implode( ', ', eZBaseStagingTransport::diffmask2array( $problems[$nodeId] ) ) . ')';
    }
    $cli->output( $out );
    if ( isset( $events[$targetId] ) )
    {
        $out = count( $events[$targetId] ) . " events pending synchronization";
        if ( $script->verboseOutputLevel() )
        {
            foreach( $events[$targetId] as $i => $event )
            {
                $events[$targetId][$i] = $event->getSyncString();
            }
            $out .= " (" . implode( ', ', $events[$targetId] ) . ')';
        }
        $cli->output( $out );
    }
    else
    {
        $cli->output( "No events pending synchronization" );
    }
}

$script->shutdown( 0 );
