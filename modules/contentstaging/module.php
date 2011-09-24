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
    'controlpanel' => array( 'script' => 'controlpanel.php',
                             'functions' => 'view',
                             'default_navigation_part' => 'ezsetupnavigationpart',
                             'ui_context' => 'default' )
);

$FunctionList = array(
    // allows viewing of controlpanel, dashboard
    'view' => array(),
    // allows triggering a sync
    /// @todo add limitations: class, subtree, section etc...
    'sync' => array()
);

?>
