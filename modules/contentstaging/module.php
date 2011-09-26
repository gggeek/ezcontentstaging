<?php
/**
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2

*/


$Module = array( 'name' => 'Content Staging' );
$ViewList = array(
    /// @todo -c rename this?
    'controlpanel' => array( 'script' => 'controlpanel.php',
                             'functions' => 'view',
                             'default_navigation_part' => 'ezsetupnavigationpart',
                             'ui_context' => 'default',
                             'unordered_params' => array( 'offset' => 'Offset' )
                             /// @todo add definition of post actions
                              ),

    /// @todo Implement a view to manage target hosts: adds new target hosts, remove them, clear (or init) sync table for a target
);

$FunctionList = array(
    // allows viewing of controlpanel, dashboard
    'view' => array(),
    // allows triggering a sync
    /// @todo add limitations: target host, class, subtree, section etc...
    'sync' => array(),
    // adds new target hosts, remove them, clear (or init) sync table for a target
    'manage' => array(),
);

?>
