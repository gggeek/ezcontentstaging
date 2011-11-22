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
$script = eZScript::instance( array( 'description' => "Script to check sync status of target feeds",
                                     'use-session'    => false,
                                     'use-modules'    => false,
                                     'use-extensions' => true ) );
$script->startup();
$options = $script->getOptions( "[targets:]",
    "",
    array( 'targets' => 'csv list of target feeds' ) );
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
        $cli->output( "Checking target: $targetId" );
        foreach( $target->checkTarget() as $nodeId => $problems )
        {
             $cli->output( "Node: $nodeId Status: $problems" );
        }
    }
    else
    {
        $cli->output( "Target: $targetId not found. Can not check!" );
    }
}

$script->shutdown( 0 );

?>