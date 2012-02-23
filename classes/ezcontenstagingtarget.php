<?php
/**
 * Class used to hold definitions of staging targets
 * Every target is defined in term of
 * - a list of content trees to sync
 * - a class to be used for transport
 * - transport-specific settings
 *
 * So far hides access to ini file, as later we might want to convert this to
 * a db-based structure
 *
 * @todo add dynamic attributes, eg. to get nr. of events per feed, to get instance of tarnsport class
 *
 * @package ezcontentstaging
 *
 * @author
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

// Extremely quick and dirty "template object" from an ini settings group
class eZContentStagingTarget
{
    protected $_attrs = array();

    function __construct( $row )
    {
        $this->_attrs = self::CamelCase2camel_case( $row );
    }

    function attributes()
    {
        return array_keys( $this->_attrs );
    }

    function attribute( $attrname )
    {
        return $this->_attrs[$attrname];
    }

    function hasAttribute( $attribute )
    {
        return in_array( $attribute, $this->attributes() );
    }

    /**
    * Returns list of target hosts defined in the system
    *
    * @return array
    */
    /*static function fetchIDList()
    {
        $ini = ezini( 'contentstagingsource.ini' );
        return $ini->variable( 'GeneralSettings', 'TargetList' );
    }*/

    static function fetchList()
    {
        $ini = eZINI::instance( 'contentstagingsource.ini' );
        $out = array();
        foreach( $ini->variable( 'GeneralSettings', 'TargetList' ) as $id )
        {
            $out[$id] = new eZContentStagingTarget( array_merge( $ini->group( 'Target_' . $id ), array( 'id' => $id ) ) );
        }
        return $out;
    }

    static function fetch( $id )
    {
        $ini = eZINI::instance( 'contentstagingsource.ini' );
        $targets = $ini->variable( 'GeneralSettings', 'TargetList' );
        if ( in_array( $id, $targets ) )
        {
            return new eZContentStagingTarget( array_merge( $ini->group( 'Target_' . $id ), array( 'id' => $id ) ) );
        }
        return null;
    }

    /**
    * Returns list of targets that should be notified of a given node
    * (assuming that there are events for that node)
    * @param int|eZContentObjectTreeNode $node
    */
    static function fetchByNode( $node )
    {
        if ( is_numeric( $node ) )
        {
            $node = eZContentObjectTreeNode::fetch( $node );

        }
        if ( ! is_a( $node, 'eZContentObjectTreeNode' ) )
        {
            return array();
        }
        $out = array();
        foreach( self::fetchList() as $id => $target )
        {
            if ( $target->includesNode( $node ) )
            {
                $out[$id] = $target;
            }
        }
        return $out;
    }

    /**
    * @return boolean
    */
    function includesNode( eZContentObjectTreeNode $node )
    {
        $nodepath = $node->attribute( 'path_string' );
        foreach( $this->_attrs['subtrees'] as $subtreeRoot )
        {
            if ( strpos( $nodepath, '/' . $subtreeRoot . '/' ) !== false )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @return boolean
     */
    function includesNodeByPath( $nodepath )
    {
        foreach( $this->_attrs['subtrees'] as $subtreeRoot )
        {
            if ( strpos( $nodepath, '/' . $subtreeRoot . '/' ) !== false )
            {
                return true;
            }
        }
        return false;
    }

    /**
    * Given an array of nodes, returns the list of those which are part of the feed
    * @param array $nodepathsarray node_id => node_path
    * @return array fileterd, in same format: node_id => node_path
    */
    function includedNodesByPath( $nodepathsarray )
    {
        $out = array();
        foreach( $this->_attrs['subtrees'] as $subtreeRoot )
        {
            foreach ( $nodepathsarray as $nodeid => $nodepath )
            {
                if ( strpos( $nodepath, '/' . $subtreeRoot . '/' ) !== false )
                {
                    $out[$nodeid] = $nodepath;
                }
            }
        }
        return $out;
    }

    /// CamelCase to camel_case conversion
    protected function CamelCase2camel_case( $array )
    {
        foreach( $array as $key => $val )
        {
            $name = strtolower( implode( '_', preg_split( '/([[:upper:]][[:lower:]]+)/', $key, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY ) ) );
            $out[$name] = $val;
        }
        return $out;
    }

    /**
    * Initializes a target by creating the necessary events and optionally syncing them:
    * - for all top level nodes, we need to sync in remote server the object-remote-id
    *   and node-remote-id
    * @return array for every source node, 0 for ok, or an error code
    */
    function initializeRootItems( $doexecute=true )
    {
        $out = array();

        $remotenodes = $this->_attrs['remote_subtrees'];
        foreach( $this->_attrs['subtrees'] as $key => $nodeID )
        {
            if ( !isset( $remotenodes[$key] ) )
            {
                eZDebug::writeError( "Remote root node not specified for feed " . $this->_attrs['name'], __METHOD__ );
                $out[] = eZContentStagingEvent::ERROR_NOREMOUTESOURCE;
                continue;
            }
            $remoteNodeID = $remotenodes[$key];

            $node = eZContentObjectTreeNode::fetch( $nodeID );
            if ( !$node )
            {
                eZDebug::writeError( "Node $subtreeRoot specified as root of feed " . $this->_attrs['name'] . " does not exist", __METHOD__ );
                $out[] = eZContentStagingEvent::ERROR_NOSOURCENODE;
                continue;
            }

            $object = $node->attribute( 'object' );
            $initData = array(
                'nodeID' => $nodeID,
                'nodeRemoteID' => $node->attribute( 'remote_id' ),
                'objectRemoteID' => $object->attribute( 'remote_id' ),
                'remoteNodeID' => $remotenodes[$key]
            );
            $evtID = eZContentStagingEvent::addEvent(
                $this->attribute( 'id' ),
                $object->attribute( 'id' ),
                eZContentStagingEvent::ACTION_INITIALIZEFEED,
                $initData,
                array( $nodeID )
            );

            if ( $doexecute && $evtID )
            {
                $ok = eZContentStagingEvent::syncEvents( array( $evtID ) );
                $out[] = $ok[$evtID];
            }
            else
            {
                $out[] = 0;
            }

        }
        return $out;
    }

    /* unused so far
    function transport()
    {
        $class = $this->attribute( 'transport_class' );
        if ( !class_exists( $class ) )
        {
            eZDebug::writeError( "Can not create transport, class $class not found", __METHOD__ );
            return null;
        }
        return new $class( $this );
    }*/

    /**
    * Checks sync status
    * @param callable $iterator
    * @return array key = noode_id, value = integer
    *
    * @bug what if a node is part of two feeds? we check it twice, but output its errors only once
    */
    function checkTarget( $iterator=null )
    {
        $out = array();

        $class = $this->attribute( 'transport_class' );
        if ( !class_exists( $class ) )
        {
            eZDebug::writeError( "Can not create transport, class $class not found", __METHOD__ );
            return array();
        }
        $transport = new $class( $this );

        $remotenodes = $this->_attrs['remote_subtrees'];
        foreach( $this->_attrs['subtrees'] as $key => $nodeID )
        {
            /*if ( !isset( $remotenodes[$key] ) )
            {
                eZDebug::writeError( "Remote root node not specified for feed " . $this->_attrs['Name'], __METHOD__ );
                $out[] = -2; //eZContentStagingEvent::ERROR_NOREMOUTESOURCE;
                continue;
            }*/
            //$remoteNodeID = $remotenodes[$key];

            $node = eZContentObjectTreeNode::fetch( $nodeID );
            if ( !$node )
            {
                eZDebug::writeError( "Node $nodeID specified as root of feed " . $this->_attrs['name'] . " does not exist", __METHOD__ );
                $out[$nodeID] = -1; //eZContentStagingEvent::ERROR_NOSOURCENODE;
                continue;
            }

            // nb: using integer-indexed arrays: must not use array_merge
            $out = $out + $this->checkNode( $node, true, $iterator, $transport );
        }

        return $out;
    }

    /**
    * @param callable $iterator
    * @todo implement a 'checked object' cache to avoid checaking same obj many times
    * @todo clear object cache after every N objects
    * @todo prevent loops
    * @todo smarter checking: if node x is not there all its children can not be there either
    */
    function checkNode( $node, $recursive=true, $iterator=false, $transport=false )
    {
        //static $testedobjects;
        //$objectID = $object->attribute( 'id' );
        //if ( !isset( $testedobjects[$objectID] ) )
        //{
        //    $objectok = $transport->checkObject( $object );
        //    $testedobjects[$objectID] = $objectok;
        //}
        //else
        //{
        //    $objectok = $testedobjects[$objectId];
        //}
        if ( $transport == false )
        {
            $class = $this->attribute( 'transport_class' );
            if ( !class_exists( $class ) )
            {
                eZDebug::writeError( "Can not create transport, class $class not found", __METHOD__ );
                return array();
            }
            $transport = new $class( $this );
        }

        $nodeok = $transport->checkNode( $node );
        $object = $node->attribute( 'object' );
        $objectok = $transport->checkObject( $object );
        $out[$node->attribute( 'node_id' )] = $nodeok | $objectok;

        if ( $recursive )
        {
            $limit = 10;
            $offset = 0;

            // seems like using $node->children is not a good idea here, while
            // subtree() gives us all nodes, regardless of anon user
            while( $subtree = $node->subTree( array(
                'Offset' => $offset,
                'Limit' => $limit,
                'Limitation' => array(),
                'MainNodeOnly' => false ) ) )
            {
                foreach ( $subtree as $child )
                {
                    // nb: using integer-indexed arrays: must not use array_merge
                    $out = $out + $this->checkNode( $child, false, $transport, $iterator );
                }
                $offset += $limit;

                /// @todo clear obj cache ?
            }
        }

        if ( is_callable( $iterator ) )
        {
            call_user_func( $iterator );
        }

        return $out;
    }

}

?>