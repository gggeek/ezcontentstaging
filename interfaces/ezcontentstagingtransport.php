<?php
/**
* Interface that every staging transport has to implement
*
* @package ezcontentstaging
*
* @author
* @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

interface eZContentStagingTransport
{
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
    * @see eZBaseStagingTransport for codes
    * @return integer
    */
    function checkNode( eZContentObjectTreeNode $node );

    /**
     * Checks a local node vs. a remote one and returns a bitmask of differences
     * @see eZBaseStagingTransport for codes
     * @return integer
     */
    function checkObject( eZContentObject $object );

    /**
    * Checks if configuration (eg ini parameters) is ok for this transport
    * @return array of string with error messages
    */
    function checkConfiguration();

    /**
     * Checks if connectivity is ok for this transport
     * @return array of string with error messages
     */
    function checkConnection();

}

?>
