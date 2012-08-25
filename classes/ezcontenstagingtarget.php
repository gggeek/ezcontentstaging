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
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

// Extremely quick and dirty "template object" from an ini settings group
class eZContentStagingTarget
{
    protected $attributes = array();

    public function __construct( $row )
    {
        // some of the parameters are optional in definition of an eZContentStagingTarget
        $this->attributes = self::CamelCase2camel_case( $row ) + array(
            "use_source_creation_dates_on_target" => "disabled",
            "use_source_owner_on_target" => "disabled"
        );
    }

    public function attributes()
    {
        return array_keys( $this->attributes );
    }

    public function attribute( $name )
    {
        return $this->attributes[$name];
    }

    public function hasAttribute( $attribute )
    {
        return in_array( $attribute, $this->attributes() );
    }

    /**
     * Returns list of target hosts defined in the system
     *
     * @return array
     */
    /*static public function fetchIDList()
    {
        $ini = ezini( 'contentstagingsource.ini' );
        return $ini->variable( 'GeneralSettings', 'TargetList' );
    }*/

    static public function fetchList()
    {
        $ini = eZINI::instance( 'contentstagingsource.ini' );
        $out = array();
        foreach ( $ini->variable( 'GeneralSettings', 'TargetList' ) as $id )
        {
            $out[$id] = new eZContentStagingTarget( array_merge( $ini->group( 'Target_' . $id ), array( 'id' => $id ) ) );
        }
        return $out;
    }

    static public function fetch( $id )
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
    static public function fetchByNode( $node )
    {
        if ( is_numeric( $node ) )
        {
            $node = eZContentObjectTreeNode::fetch( $node );

        }
        if ( ! $node instanceof eZContentObjectTreeNode )
        {
            return array();
        }
        $out = array();
        foreach ( self::fetchList() as $id => $target )
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
    protected function includesNode( eZContentObjectTreeNode $node )
    {
        return $this->includesNodeByPath( $node->attribute( 'path_string' ) );
    }

    /**
     * @return boolean
     */
    public function includesNodeByPath( $nodepath )
    {
        foreach ( $this->attributes['subtrees'] as $subtreeRoot )
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
    public function includedNodesByPath( $nodepathsarray )
    {
        $out = array();
        foreach ( $this->attributes['subtrees'] as $subtreeRoot )
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
        $result = array();
        foreach ( $array as $key => $val )
        {
            $name = strtolower( implode( '_', preg_split( '/([[:upper:]][[:lower:]]+)/', $key, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY ) ) );
            $result[$name] = $val;
        }
        return $result;
    }

    /**
     * Initializes a target by creating the necessary events and optionally syncing them:
     * - for all top level nodes, we need to sync in remote server the object-remote-id
     *   and node-remote-id
     * @return array for every source node, 0 for ok, or an error code
     * @todo !important refactor: rename this method?
     */
    public function initializeRootItems()
    {
        $out = array();

        $transport = $this->transport();
        // exit with specific error if transport is null
        if ( $transport == false )
        {
            return array( eZContentStagingEvent::ERROR_NOTRANSPORTCLASS );
        }

        $remoteNodes = $this->attributes['remote_subtrees'];
        foreach ( $this->attributes['subtrees'] as $key => $nodeID )
        {
            if ( !isset( $remoteNodes[$key] ) )
            {
                eZDebug::writeError( "Remote root node not specified for feed " . $this->attributes['name'], __METHOD__ );
                $out[] = eZContentStagingEvent::ERROR_NOREMOUTESOURCE;
                continue;
            }
            $remoteNodeID = $remoteNodes[$key];

            $node = eZContentObjectTreeNode::fetch( $nodeID );
            if ( !$node )
            {
                eZDebug::writeError( "Node $nodeID specified as root of feed " . $this->attributes['name'] . " does not exist", __METHOD__ );
                $out[] = eZContentStagingEvent::ERROR_NOSOURCENODE;
                continue;
            }

            $out[] = $transport->initializeSubtree( $node, $remoteNodeID );
        }
        return $out;
    }

    protected function transport()
    {
        $class = $this->attribute( 'transport_class' );
        if ( !class_exists( $class ) )
        {
            eZDebug::writeError( "Can not create transport, class $class not found", __METHOD__ );
            return null;
        }
        return new $class( $this );
    }

    /**
     * Checks sync status for all nodes in the feed
     * @param callable $iterator
     * @return array key = noode_id, value = integer
     *
     * @bug what if a node is part of two feeds? we check it twice, but output its errors only once
     */
    public function checkTarget( $iterator = null )
    {
        $out = array();

        $transport = $this->transport();

        //$remotenodes = $this->attributes['remote_subtrees'];
        foreach ( $this->attributes['subtrees'] as $key => $nodeID )
        {
            /*if ( !isset( $remotenodes[$key] ) )
            {
                eZDebug::writeError( "Remote root node not specified for feed " . $this->attributes['Name'], __METHOD__ );
                $out[] = -2; //eZContentStagingEvent::ERROR_NOREMOUTESOURCE;
                continue;
            }*/
            //$remoteNodeID = $remotenodes[$key];

            $node = eZContentObjectTreeNode::fetch( $nodeID );
            if ( !$node )
            {
                eZDebug::writeError( "Node $nodeID specified as root of feed " . $this->attributes['name'] . " does not exist", __METHOD__ );
                $out[$nodeID] = -1; //eZContentStagingEvent::ERROR_NOSOURCENODE;
                continue;
            }

            // nb: using integer-indexed arrays: must not use array_merge
            $out = $out + $this->checkNode( $node, true, $transport, $iterator );
        }

        return $out;
    }

    /**
     * @param callable $iterator
     * @return array
     * @todo implement a 'checked object' cache to avoid checaking same obj many times
     * @todo clear object cache after every N objects
     * @todo prevent loops
     * @todo smarter checking: if node x is not there all its children can not be there either
     */
    public function checkNode( $node, $recursive = true, $transport = false, $iterator = false )
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
            $transport = $this->transport();
            // exit with specific error if transport is null
            if ( $transport == false )
            {
                return array( $node->attribute( 'node_id' ) => eZBaseStagingTransport::DIFF_UNKNOWNTRANSPORT );
            }
        }

        $out = array(
            $node->attribute( 'node_id' ) => $transport->checkNode( $node ) | $transport->checkObject( $node->attribute( 'object' ) )
        );

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

    /**
     * Checks if all configuration for the target is fine
     * @return array of string (error messages)
     */
    static public function checkConfiguration( $targetId )
    {
        $out = array();

        $ini = eZINI::instance( 'contentstagingsource.ini' );

        $targets = $ini->variable( 'GeneralSettings', 'TargetList' );
        if ( !in_array( $targetId, $targets ) )
        {
            $out[] = "Feed not defined in file contentstagingsource.ini, block 'GeneralSettings', parameter 'TargetList'";
        }

        $group = 'Target_' . $targetId;
        if ( !$ini->hasGroup( $group ) )
        {
            $out[] = "Feed not defined in file contentstagingsource.ini, block '$group'";
            return $out;
        }

        if ( !$ini->hasVariable( $group, 'Subtrees' ) || !is_array( $sourceroots = $ini->variable( $group, 'Subtrees' ) ) || empty( $sourceroots ) )
        {
            $out[] = "Feed has no source root nodes defined in file contentstagingsource.ini, block '$group', parameter 'Subtrees'";
        }
        /* this might actually be supported (to validate): have the same root node twice
        if ( count( $sourceroots ) != count( array_unique( $sourceroots ) ) )
        {
            $out[] = "Feed has same source root defined twice in file contentstagingsource.ini, block '$group', parameter 'Subtrees'";
        }*/
        if ( !$ini->hasVariable( $group, 'RemoteSubtrees' ) || !is_array( $remoteroots = $ini->variable( $group, 'RemoteSubtrees' ) ) || count( $remoteroots ) == 0 )
        {
            $out[] = "Feed has no target root nodes defined in file contentstagingsource.ini, block '$group', parameter 'RemoteSubtrees'";
        }
        // since $sourceroots and $remoteroots could be hashes, we need to check their keys: all keys in source should be in remote as well
        if ( count( array_diff( array_keys( $sourceroots ), array_keys( $remoteroots ) ) ) )
        {
             $out[] = "Source root nodes and remote root nodes differ in file contentstagingsource.ini, block '$group', parameters 'Subtrees' and 'RemoteSubtrees' ";
        }
        if ( count( $remoteroots ) != count( array_unique( $remoteroots ) ) )
        {
            /// @todo if the same target root is defined twice or more,
            ///       we can allow it if it is tied to the same source root (or init will not work)
            $out[] = "Feed has same remote root defined twice in file contentstagingsource.ini, block '$group', parameter 'RemoteSubtrees'";
        }
        foreach ( $sourceroots as $root )
        {
            if ( !eZContentObjectTreeNode::fetch( $root ) )
            {
                $out[] = "Source root node $root does not exist in file contentstagingsource.ini, block '$group', parameter 'Subtrees'";
            }
        }

        if ( !$ini->hasVariable( $group, 'TransportClass' ) || $ini->variable( $group, 'TransportClass' ) == '' )
        {
            $out[] = "Feed $targetId has no transport defined in file contentstagingsource.ini, block '$group', parameter 'TransportClass'";
        }
        $class = $ini->variable( $group, 'TransportClass' );
        if ( !class_exists( $class ) )
        {
            $out[] = "Feed $targetId has transport class '$class' defined in file contentstagingsource.ini, block '$group', parameter 'TransportClass', but class does not exist";
        }
        else
        {
            $target = self::fetch( $targetId );
            if ( $target )
            {
                $transport = new $class( $target );
                $out = array_merge( $out, $transport->checkConfiguration() );
            }
        }

        return $out;
    }

    /**
     * Checks if the transport layer can connect to the target server
     * NB: returns no errors if transport class is not available - use checkConfiguration for that
     * @return array of string (error messages)
     */
    public function checkConnection()
    {
        $transport = $this->transport();
        if ( !$transport )
        {
            return array();
        }
        return $transport->checkConnection();
    }

    /**
     * Checks if the feed has been successfully initializated, by asking the
     * transport layer (which takes care of handling the initialization).
     * NB: returns no errors if transport class is not available - use checkConfiguration for that
     * @return array of string (error messages)
     */
    function checkInitialization()
    {
        $transport = $this->transport();
        if ( !$transport )
        {
            return array();
        }

        $out = array();
        $remotenodes = $this->attributes['remote_subtrees'];
        foreach ( $this->attributes['subtrees'] as $key => $nodeID )
        {
            if ( !isset( $remotenodes[$key] ) )
            {
                $out[] = "Remote root node not specified for feed " . $this->attributes['name'];
                continue;
            }
            $remoteNodeID = $remotenodes[$key];

            $node = eZContentObjectTreeNode::fetch( $nodeID );
            if ( !$node )
            {
                $out[] = "Node $nodeID specified as root of feed " . $this->attributes['name'] . " does not exist";
                continue;
            }

            if ( count( $errors = $transport->checkSubtreeInitialization( $node, $remoteNodeID ) ) != 0 )
            {
                $out = array_merge( $out, $errors );
            }
        }
        return $out;
    }

}
