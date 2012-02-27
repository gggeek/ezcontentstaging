<?php
/**
 * Cli script that checks status of one or more target feeds
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @todo give some feedback while analyzing feeds (eg. one dot per node)
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
        $cli->output( "Checking target: $targetId" );

        // @todo to be implemented
        //$nodeCount = $target->nodeCount();
        //$cli->output( "Nodes to check: $nodeCount" );
        //$script->resetIteration( $nodeCount, 0 );
        //$tosync = $target->checkTarget( 'iterate' );

        $tosync = $target->checkTarget();
        foreach ( $tosync as $nodeId => $problems )
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
                /// @todo decode to readable status the discrepancies
                $cli->output( "Node: $nodeId Status: $problems" );
            }
        }
    }
    else
    {
        $cli->output( "Target: $targetId not found. Can not check!" );
    }
}

$script->shutdown( 0 );
