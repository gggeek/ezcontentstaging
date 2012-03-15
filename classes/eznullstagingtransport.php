<?php
/**
 * Dummy class used to sync content to remote servers:
 * always returns OK
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZNullStagingTransport implements eZContentStagingTransport
{

    function __construct( eZContentStagingTarget $target )
    {

    }

    function initializeSubtree( eZContentObjectTreeNode $node, $remoteNodeID )
    {
        return 0;
    }

    function syncEvents( array $events )
    {
        return 0;
    }

    function checkNode( eZContentObjectTreeNode $node )
    {
        return 0;
    }

    function checkObject( eZContentObject $object )
    {
        return 0;
    }

    function checkConfiguration()
    {
        return array();
    }

    function checkConnection()
    {
        return array();
    }
    function checkSubtreeInitialization( eZContentObjectTreeNode $node, $remoteNodeID )
    {
        return array();
    }
}
