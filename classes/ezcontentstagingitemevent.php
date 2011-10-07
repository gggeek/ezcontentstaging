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
 * @todo document the expected format for "data"  for every type of event
 */

class eZContentStagingItemEvent extends eZPersistentObject
{
    /// @todo...
    const ACTION_ADDLOCATION = 'addlocation';
    const ACTION_REMOVELOCATION = 'removelocation';

    static function definition()
    {
        return array( 'fields' => array( 'target_id' => array( 'name' => 'TargetID',
                                                        'datatype' => 'string',
                                                        'required' => true ),
                                         'object_id' => array( 'name' => 'ObjectID',
                                                               'datatype' => 'integer',
                                                               'required' => true,
                                                               'foreign_class' => 'eZContentObject',
                                                               'foreign_attribute' => 'id',
                                                               'multiplicity' => '1..*' ),
                                         'id' => array( 'name' => 'ID',
                                                        'datatype' => 'integer',
                                                        'required' => true ),
                                         'created' => array( 'name' => 'Created',
                                                             'datatype' => 'integer',
                                                             'required' => true ),
                                         'type' => array( 'name' => 'Type',
                                                          'datatype' => 'string',
                                                          'required' => true ),
                                         'data' => array( 'name' => 'Data',
                                                          'datatype' => 'text' ) ),
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

    /// transaction_unsafe
    static function removeByItem( $target_id, $object_id )
    {
        return self::removeObject(
            self::definition(), array( 'target_id' => $target_id, 'object_id' => $object_id )
        );
    }

    function getData()
    {
        return json_decode( $this->Data, true );
    }

}

?>