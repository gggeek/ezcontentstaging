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
    /// list of all defined feeds
    'feeds' => array( 'script' => 'feeds.php',
                      'functions' => 'view',
                      'default_navigation_part' => 'ezsetupnavigationpart',
                      'ui_context' => 'default',
                      'unordered_params' => array( 'offset' => 'Offset' ),
                      'params' => array( 'target_id' )
                      /// @todo add definition of post actions
                    ),
    /// view of a single feed, also used to sync it
    'feed' => array( 'script' => 'feed.php',
                     'functions' => 'view',
                     'default_navigation_part' => 'ezsetupnavigationpart',
                     'ui_context' => 'default',
                     'unordered_params' => array( 'offset' => 'Offset' ),
                     'params' => array( 'target_id' )
                     /// @todo add definition of post actions
                   ),
    /// view used to sync a single item
    'sync' => array( 'script' => 'sync.php',
                     'functions' => 'sync',
                     'default_navigation_part' => 'ezsetupnavigationpart',
                     'ui_context' => 'default',
                     'params' => array( 'object_id', 'target_id' ) ),

    /// @todo Implement a view to manage target hosts: adds new target hosts, remove them, clear (or init) sync table for a target
);

$FunctionList = array(
    // allows viewing of feed, dashboard, needing-sync status in ezwt
    'view' => array(),
    // allows triggering a sync
    /// @todo add limitations: target host, class, subtree, section etc...
    'sync' => array(),
    // adds new target hosts, remove them, clear (or init) sync table for a target
    'manage' => array(),
);

?>
