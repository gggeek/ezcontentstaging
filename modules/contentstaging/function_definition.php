<?php
/**
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

$FunctionList = array(

    // returns the list of servers to which an object needs to sync, as an array( srv_id => target obj )
    'objectsynctargets' => array(
        'name' => 'objectsynctargets',
        'call_method' => array(
           'class'  => 'eZContentStagingFunctionCollection',
           'method' => 'fetchObjectSyncTargets'  ),
           'parameters' => array( array( 'name'     => 'object_id',
                                         'type'     => 'string',
                                         'required' => true ) ) ),

    'synctarget' => array(
        'name' => 'synctarget',
        'call_method' => array(
               'class'  => 'eZContentStagingFunctionCollection',
               'method' => 'fetchSyncTarget'  ),
        'parameters' => array( array( 'name'     => 'target_id',
                                      'type'     => 'string',
                                      'required' => true ) ) ),

    'syncitems'  => array(
        'name' => 'syncitems',
        'call_method' => array(
            'class'  => 'eZContentStagingFunctionCollection',
            'method' => 'fetchSyncItems' ),
        'parameters' => array( array( 'name'     => 'target_id',
                                      'type'     => 'string',
                                      'required' => false,
                                      'default'  => '' ),
                               array( 'name'     => 'offset',
                                      'type'     => 'integer',
                                      'required' => false,
                                      'default'  => 0 ),
                               array( 'name'     => 'limit',
                                      'type'     => 'integer',
                                      'required' => false,
                                      'default'  => 0 ) ) ),

    'syncitems_count'  => array(
        'name' => 'syncitems_count',
        'call_method' => array(
            'class'  => 'eZContentStagingFunctionCollection',
            'method' => 'fetchSyncItemsCount' ),
        'parameters' => array( array( 'name'     => 'target_id',
                                      'type'     => 'string',
                                      'required' => false,
                                      'default'  => '' ) ) ),
);

?>