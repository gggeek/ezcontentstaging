<?php
/**
 * Interface that every staging transport has to implement
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

interface eZContentStagingTransport
{
    public function __construct( eZContentStagingTarget $target );

    /**
     * This method takes an array of events becasue some transports might have
     * optimized ways of sending them in a single call instead of making one call
     * per event
     *
     * @param array of eZContentStagingEvent $events
     * @return array values: 0 on sucess, a string (or other int?) on error
     */
    public function syncEvents( array $events );

    /**
     * Called once for each subtree that makes up a feed, this method should
     * take all necessary actions to insure that successive calls are fine.
     * It should be sufficient to init any feed only once, but multiple
     * initializations should not have a destructive effect
     * @retrun integer 0 on success
     */
    function initializeSubtree( eZContentObjectTreeNode $node, $remoteNodeID );

    /**
     * Checks a local node vs. a remote one and returns a bitmask of differences
     * @see eZBaseStagingTransport for codes
     * @return integer
     */
    public function checkNode( eZContentObjectTreeNode $node );

    /**
     * Checks a local object vs. a remote one and returns a bitmask of differences
     * @see eZBaseStagingTransport for codes
     * @return integer
     */
    public function checkObject( eZContentObject $object );

    /**
     * Checks if configuration (eg ini parameters) is ok for this transport
     * @return array of string with error messages
     */
    public function checkConfiguration();

    /**
     * Checks if connectivity is ok for this transport
     * @return array of string with error messages
     */
    public function checkConnection();

    /**
     * Checks if initialization is ok for a given subtree.
     * @return array of string with error messages
     */
    function checkSubtreeInitialization( eZContentObjectTreeNode $node, $remoteNodeID );

}
