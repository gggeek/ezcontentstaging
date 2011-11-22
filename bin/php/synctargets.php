<?php
/**
 * Cli script that checks status of one or more target feeds
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => "Execute full sync of pending events for target feeds",
                                     'use-session'    => false,
                                     'use-modules'    => false,
                                     'use-extensions' => true ) );
$script->startup();
$options = $script->getOptions( "[targets:][sync]",
    "",
    array( 'targets' => 'list of target feeds (csv)' ) );
$script->initialize();

$targets = $options['targets'];

if ( $targets == '' )
{
    $ini = eZINI::instance( 'contentstaging.ini' );
    $targets = $ini->variable( 'GeneralSettings', 'TargetList' );
}
else
{
    $targets = explode( ',', $targets );
}

foreach( $targets as $targetId )
{
    $target = eZContentStagingTarget::fetch( $targetId );
    if ( $target )
    {
        $cli->output( "Syncing target: $targetId" );

        $events = eZContentStagingEvent::fetchList( $targetId );
        $eventCount = count( $events );
        $cli->output( "Events to synchronize: $eventCount" );
        $script->resetIteration( $eventCount, 0 );

        /// @todo pass a callable function to show advance progress
        foreach( eZContentStagingEvent::syncEvents( $events ) as $id => $resp )
        {
            if ( $resp === 0 )
            {
                $cli->output( "Event $id: OK" );
            }
            else
            {
                $cli->error( "Event $id: KO: $resp" );
            }
        }
    }
    else
    {
        $cli->output( "Target: $targetId not found. Can not check!" );
    }
}

$script->shutdown( 0 );

?>