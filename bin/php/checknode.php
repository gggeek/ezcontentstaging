<?php
/**
 * Cli script that checks status of one or more target feeds
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://ez.no/Resources/Software/Licenses/eZ-Business-Use-License-Agreement-eZ-BUL-Version-2.1 eZ Business Use License Agreement eZ BUL Version 2.1
 *
 * @todo give some feedback while analyzing feeds (eg. one dot per node)
 */

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => "Check sync status of a single node in target feeds",
                                     'use-session'    => false,
                                     'use-modules'    => false,
                                     'use-extensions' => true ) );
$script->startup();
$options = $script->getOptions( "[sync]",
    "[nodeid]",
    array( 'sync' => 'when specified, synchronization events will be generated to bring target to parity with current installation' ) );
$script->initialize();

if ( count( $options['arguments'] ) != 1 )
{
    $script->shutdown( 1, 'wrong argument count' );
}
$nodeid = $options['arguments'][0];

$node = eZContentObjectTreeNode::fetch( $nodeid );
if ( !$node )
{
    $cli->output( "Node $nodeid not found in content" );
    $script->shutdown( 0 );
}

$targets = eZContentStagingTarget::fetchByNode( $node );
if ( !count( $targets ) )
{
    $cli->output( "Node $nodeid not found in any feed" );
    $script->shutdown( 0 );
}
$cli->output( "Node $nodeid found in feeds: " . implode( ', ', array_keys( $targets ) ) );

foreach ( $targets as $targetId => $target )
{
    $cli->output( "" );
    $problems = $target->checkNode( $node, false );
    $cli->output( "Target: $targetId, Status: " . reset( $problems ) );
}

$script->shutdown( 0 );
