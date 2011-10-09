<?php
/**
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 */


class eZContentStagingJSCoreFunctions
{
    /**
    * @param array $args ( 0 => node_id,  1 => target_id )
    * @return array
    *
    * @todo add i18n of returned messages
    */
    static function syncnode( $args )
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
            $events = eZContentStagingEvent::fetchByNode( $args[0], null, $args[1] );
        }
        if ( count( $events ) )
        {
            $syncErrors = array();
            $syncResults = array();
            foreach( eZContentStagingEvent::syncEvents( $events ) as $id => $resultCode )
            {
                $event = $tosync[$id];
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
        else
        {
            return array( 'errors' => array( 'No events found for node ' . (int)$args[0] ) );
        }
    }
}

?>