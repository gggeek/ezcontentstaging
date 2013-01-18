<?php
/**
 * Cli script that checks status of one or more target feeds
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @todo allow user to limit scan depth
 * @todo internationalize output
 */

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => "Check sync status of all nodes in target feeds",
                                     'use-session'    => false,
                                     'use-modules'    => false,
                                     'use-extensions' => true ) );
$script->startup();
$options = $script->getOptions( "[targets:][sync]",
    "",
    array( 'targets' => 'list of target feeds (csv)',
           'sync' => 'when specified, synchronization events will be generated to bring target to parity with current installation' ) );
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
    if ( $target )
    {
        $cli->output( "" );
        $cli->output( "Checking target: $targetId" );

        $nodeCount = $target->nodeCount();
        $cli->output( "$nodeCount nodes to check in " . count( $target->attribute( 'subtrees' ) ) . " subtrees" );
        $script->resetIteration( $nodeCount, 0 );

        // love closures! php 5.3 only!
        $toSync = $target->checkTarget(
            function ( $status ) use ( $cli, $script )
            {
                $script->iterate( $cli, $status == 0 );
            }
        );
        foreach ( $toSync as $nodeId => $problems )
        {

            if ( $options['sync'] )
            {
                if ( $problems == 0 )
                {
                    $cli->output( "Node: $nodeId: OK - nothing to do" );
                }
                else if ( $problems & DIFF_TRANSPORTERROR )
                {
                    $cli->output( "Node: $nodeId: KO - error in retrieveing remote status" );
                }
                else
                {
                    $cli->error( "Node: $nodeId: KO - status $problems can not (yet) be handled" );
                }
            }
            else
            {
                $out = "Node: $nodeId Status: $problems";
                if ( $script->verboseOutputLevel() && $problems[$nodeId] != 0 )
                {
                    $out .= " (" . implode( ', ', eZBaseStagingTransport::diffmask2array( $problems[$nodeId] ) ) . ')';
                }
                $cli->output( $out );
            }
        }
    }
    else
    {
        $cli->output( "Target: $targetId not found. Can not check!" );
    }
}

$script->shutdown( 0 );
