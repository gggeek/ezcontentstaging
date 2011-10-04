<?php
/**
 *
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @todo use constants instaed of strings?
 */

class eZContentStagingItemEvent extends eZPersistentObject
{
    /// @todo...
    const ACTION_ADDLOCATION = 'addlocation';
    const ACTION_REMOVELOCATION = 'romovelocation';

    static function definition()
    {
        return array( 'fields' => array( 'target_id' => array( 'name' => 'TargetID',
                                                        'datatype' => 'string',
                                                        'required' => true ),
                                         'object_id' => array( 'name' => 'ObjectID',
                                                               'datatype' => 'integer',
                                                               'required' => true ),
                                         'id' => array( 'name' => 'ID',
                                                        'datatype' => 'integer',
                                                        'required' => true ),
                                         'created' => array( 'name' => 'Created',
                                                             'datatype' => 'integer',
                                                             'required' => true ),
                                         'type' => array( 'name' => 'Type',
                                                          'datatype' => 'string',
                                                          'required' => true ),
                                         'data' => array( 'name' => 'data',
                                                          'datatype' => 'string' ) ),
                      'keys' => array( 'target_id', 'object_id', 'id' ),
                      'function_attributes' => array( ),
                      //'increment_key' => 'id',
                      'class_name' => 'eZContentStagingItemEvent',
                      'sort' => array( 'id' => 'asc' ),
                      'name' => 'ezcontentstaging_item_event' );
    }

    static function fetchByItem( $target_id, $object_id, $asObject = true )
    {
        return self::fetchObjectList( self::definition(),
                                      null,
                                      array( 'target_id' => $target_id, 'object_id' => $object_id ),
                                      null,
                                      array(),
                                      $asObject );
    }

    static function removeByItem( $target_id, $object_id )
    {
        /// @todo delete all events related to an item ...
    }

}

?>