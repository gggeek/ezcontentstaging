<?php
/**
* Interface that every staging transport has to implement
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

interface eZContentStagingTransport
{
    const DIFF_TRANSPORTERROR = 1;

    const DIFF_NODE_MISSING = 2;
    const DIFF_NODE_PARENT = 4;
    const DIFF_NODE_VISIBILITY = 8;
    const DIFF_NODE_SORTFIELD = 16;
    const DIFF_NODE_SORTORDER = 32;

    const DIFF_OBJECT_MISSING = 64;
    const DIFF_OBJECT_SECTION = 128;
    const DIFF_OBJECT_STATE = 256;
    const DIFF_OBJECT_ALWAYSAVAILABLE = 1024;

    function __construct( eZContentStagingTarget $target );

    /**
    * This method takes an array of events becasue some transports might have
    * optimized ways of sending them in a single call instead of making one call
    * per event
    *
    * @param array of eZContentStagingEvent $events
    * @return array values: 0 on sucess, a string (or other int?) on error
    */
    function syncEvents( array $events );

    /**
    * Checks a local node vs. a remote one and returns a bitmask of differences
    * @return integer
    */
    function checkNode( eZContentObjectTreeNode $node );

    /**
     * Checks a local node vs. a remote one and returns a bitmask of differences
     * @return integer
     */
    function checkObject( eZContentObject $object );
}

?>
