<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 */

class eZContentStagingJSCoreFunctions
{
    /**
     * @param array $args ( 0 => node_id,  1 => target_id, 2 => language (optional) )
     * @return array
     *
     * @todo add i18n of returned messages
     */
    static public function syncnode( $args )
    {
        if ( count( $args ) < 1 )
        {
            return array( 'errors' => array( 'Wrong parameter count' ) );
        }
        if ( count( $args ) < 2 )
        {
            $events = eZContentStagingEvent::fetchByNode( $args[0] );
        }
        else
        {
            $events = eZContentStagingEvent::fetchByNode( $args[0], null, $args[1], true, @$args[2] );
        }
        if ( empty( $events ) )
        {
            return array( 'errors' => array( 'No events found for node ' . (int)$args[0] ) );
        }

        $syncErrors = array();
        $syncResults = array();
        foreach ( eZContentStagingEvent::syncEvents( $events ) as $id => $resultCode )
        {
            if ( $resultCode !== 0 )
            {
                $syncErrors[] = "Event $id: failure ($resultCode)";
            }
            else
            {
                $syncResults[] = "Event $id: success";
            }
        }
        return array( 'errors' => $syncErrors, 'results' => $syncResults );
    }
}
