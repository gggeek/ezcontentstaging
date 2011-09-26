<?php
/**
* The persistent class used to store information about which objects need to be
* synced to target servers - every content object that is modified gets a line in
* the eZContentStagingItem table for every existing target host.
* When the object is synced, the line is removed.
* If the object is modified again before its sync, the modified and to_sync values
* are aupdated, but no new line is created.
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

class eZContentStagingItem extends eZPersistentObject
{

    const SYNC_PUBLICATION = 1;
    const SYNC_DELETION = 2;
    const SYNC_VISIBILITY = 4;
    const SYNC_NODES = 8;
    const SYNC_SECTION = 16;
    const SYNC_STATES = 32;
    const SYNC_SORTORDER = 64;

    static function definition()
    {
        return array( 'fields' => array( 'target_id' => array( 'name' => 'TargetID',
                                                        'datatype' => 'string',
                                                        'required' => true ),
                                         'object_id' => array( 'name' => 'ObjectID',
                                                               'datatype' => 'integer',
                                                               'required' => true ),
                                         // we store a custom modification date of object, as it includes metadata modifications
                                         'modified' => array( 'name' => 'Modified',
                                                              'datatype' => 'integer',
                                                              'required' => true ),
                                         'to_sync' => array( 'name' => 'ToSync',
                                                             'datatype' => 'integer',
                                                             'required' => true ),
                                         // are these fields actually needed ?
                                         /*'synced' => array( 'name' => 'SyncDate',
                                                            'datatype' => 'integer',
                                                            'default' => null ),
                                         'sync-failures' => array( 'name' => 'SyncFailures',
                                                                   'datatype' => 'integer',
                                                                   'default' => 0,
                                                                   'required' => true )*/ ),
                      'keys' => array( 'target_id', 'object_id' ),
                      'function_attributes' => array( 'object' => 'getObject',
                                                      'target' => 'getTarget' ),
                      //'increment_key' => 'id',
                      'class_name' => 'eZContentStagingItem',
                      'sort' => array( 'modified' => 'desc' ),
                      'name' => 'ezcontentstaging_item' );
    }

    /// fetch a specific sync item
    static function fetch( $target_id, $object_id, $asObject = true )
    {
        return self::fetchObject( self::definition(),
                                  null,
                                  array( 'target_id' => $target_id, 'object_id' => $object_id ),
                                  $asObject );
    }

    /// fetch all items that need to be synced to a given server
    static function fetchByTarget( $target_id, $asObject = true )
    {
        // @todo ...
        return self::fetchObject( self::definition(),
                                  null,
                                  array( 'target_id' => $target_id ),
                                  $asObject );
    }

    /// Returns content object that this sync item refers to
    function getObject()
    {
        return eZContentObject::fetch( $this->ObjectID );
    }

    function getTarget()
    {
        return eZContentStagingTarget::fetch( $this->TargetID );
    }

    /**
    * q: shall we make this a static function?
    * @return bool false on error (shall we return an error code instead?
    */
    function sync()
    {
        // use transport class to sync the current changes
        $target = eZContentStagingTarget::fetch( $this->TargetID );
        $class = $target->attribute( 'TransportClass' );
        $transport = new $class( $target );

        /// @todo add logging (ezdebug.ini based)
        $result = $trasport->sync( $this );

        /// @todo ...
        // if ok: check if by chance someone else updated the node while we where
        // syncing (modified, to_sync)
        // - if no: remove line from table
        // - if yes: sync again

        // if ko: log error at least using debug
        // (in the future, we might add automatic disabling of sync after 3 consecutive failures)
    }
}

?>
