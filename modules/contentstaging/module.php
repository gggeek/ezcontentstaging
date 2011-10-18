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
                      'unordered_params' => array( 'offset' => 'Offset' )
                      /// @todo add definition of post actions to manage feeds: add/remove/reset/initialize/full sync
                    ),
    /// view of a single feed, also used to sync its events
    'feed' => array( 'script' => 'feed.php',
                     'functions' => 'view',
                     'default_navigation_part' => 'ezsetupnavigationpart',
                     'ui_context' => 'default',
                     'unordered_params' => array( 'offset' => 'Offset' ),
                     'params' => array( 'target_id' ),
                     /// @todo add definition of more post actions
                     'single_post_actions' => array( 'SyncEventsButton' => 'SyncEvents' )
                   ),
    /**
      @todo move all actions from other views into this one - cleaner architecture
    // where all the actions take place
    'actions' => array( 'script' => 'actions.php',
                        'functions' => 'sync or manage',
                        'default_navigation_part' => 'ezsetupnavigationpart',
                        'ui_context' => 'default',
                        /// @todo add definition of post actions to manage feeds: add/remove/reset/initialize/full sync
                        'single_post_actions' => array( 'SyncEventsButton' => 'SyncEvents' )
                       )
    */

    /* moved to ezjscore functions
    /// view used to sync a set of events
    'syncevents' => array( 'script' => 'syncevents.php',
                           'functions' => 'sync',
                           'default_navigation_part' => 'ezsetupnavigationpart',
                           'ui_context' => 'default',
                           'params' => array( 'event_ids' ) ), */
    /*
    /// view used to sync a node - DEPRECATED
    'syncnode' => array( 'script' => 'sync.php',
                         'functions' => 'sync',
                         'default_navigation_part' => 'ezsetupnavigationpart',
                         'ui_context' => 'default',
                         'params' => array( 'node_id', 'target_it' ) ) */
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
