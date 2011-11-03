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
 * @todo add dynamic attributes, eg. to get nr. of events per feed
 *
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
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
        $ini = ezini( 'contentstaging.ini' );
        return $ini->variable( 'GeneralSettings', 'TargetList' );
    }*/

    static function fetchList()
    {
        $ini = eZINI::instance( 'contentstaging.ini' );
        foreach( $ini->variable( 'GeneralSettings', 'TargetList' ) as $id )
        {
            $out[$id] = new eZContentStagingTarget( array_merge( $ini->group( 'Target_' . $id ), array( 'id' => $id ) ) );
        }
        return $out;

    }

    static function fetch( $id )
    {
        $ini = eZINI::instance( 'contentstaging.ini' );
        $targets = $ini->variable( 'GeneralSettings', 'TargetList' );
        if ( isset( $targets[$id] ) )
        {
            return new eZContentStagingTarget( array_merge( $ini->group( 'Target_' . $id ), array( 'id' => $id ) ) );
        }
        return null;
    }

    /**
    * Returns list of targets that should be notified of a given node
    * (assuming that there are events for that node)
    */
    static function fetchByNode( eZContentObjectTreeNode $node )
    {
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
    * Given an array on nodes, returns the list of those which are part of the feed
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
    function CamelCase2camel_case( $array )
    {
        foreach( $array as $key => $val )
        {
            $name = strtolower( implode( '_', preg_split( '/([[:upper:]][[:lower:]]+)/', $key, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY ) ) );
            $out[$name] = $val;
        }
        return $out;
    }

    /**
    * Initializes a target by creating the necessary items/events:
    * - for all top level nodes, we need to sync in remote server the object-id
    *   (and node-id ???)
    */
    function initializeRootItems()
    {
        foreach( $this->_attrs['subtrees'] as $subtreeRoot )
        {
            $node = eZContentObjecTreeNode::fetch( $subtreeRoot );
            if ( $node )
            {
                /// @todo ...
            }
            else
            {
                eZDebug::writeError( "Node $subtreeRoot specified as root of feed " . $this->_attrs['Name'] . " does not exist", __METHOD__ );
            }
        }
    }
}

?>