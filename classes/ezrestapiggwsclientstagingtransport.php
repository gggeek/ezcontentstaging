<?php
/**
* Class used to sync content to remote servers
* - using ggws extension for the http layer
* - interfacing with the REST api for content creation
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2

*/

class ezRestApiGGWSClientStagingTransport implements eZContentStagingTransport
{

    function __construct( eZContentStagingTarget $target )
    {
        $this->target = $target;
    }

    function sync( eZContentStagingItem $item )
    {
        $events = $item->attribute( 'events' );
        foreach( self::coalesceEvents( $events ) as $event )
        {
            switch( $event->attribute( 'type' ) )
            {
                case eZContentStagingItemEvent::ACTION_ADDLOCATION:
                    $data = $event->getData();
                    //$RemObjID = $this->getRemObjID( $data['objectRemoteId'] );
                    /// @todo test that $RemObjID is not null
                    $method = 'PUT';
                    $url = "/content/objects/remote/$RemObjID/locations?parentRemoteId={$data['parentRemoteId']}";
                    $payload = array(
                        /// @todo transcode values
                        'priority' => $data['priority'],
                        'remoteId' => $data['remoteId'],
                        'sortField' => $data['sortField'],
                        'sortOrder' => $data['sortOrder']
                        );
                    $out = $this->restCall( $method, $url, $payload );
                    break;
                case 'delete':
                    ;
                    break;
                case 'hide':
                ;
                    break;
                case 'move':
                    ;
                    break;
                case 'publish':
                    ;
                    break;
                case eZContentStagingItemEvent::ACTION_REMOVELOCATION:
                    $data = $event->getData();
                    $method = 'DELETE';
                    $url = "/content/locations?remoteId={$data['remoteId']}";
                    $out = $this->restCall( $method, $url );
                    break;
                case 'removetranslation':
                    ;
                    break;
                case eZContentStagingItemEvent::ACTION_UPDATESECTION:
                    $data = $event->getData();
                    $method = 'PUT';
                    $url = "/content/objects/remote/$RemObjID/section?sectionId={$data['sectionId']}";
                    $out = $this->restCall( $method, $url );
                    break;
                case 'sort':
                    ;
                    break;
                case 'swap':
                    ;
                    break;
                case 'updatealwaysavailable':
                    ;
                    break;
                case 'updateinitiallanguage':
                    ;
                    break;
                case 'updatemainassignment':
                    ;
                    break;
                case 'updatepriority':
                    ;
                    break;
            }
        }

        return 0;
    }

    /*protected function getRemObjID( $RemObjRemoteID )
    {
        $out = $this->restCall( 'GET', "/content/objects?remoteId=$RemObjRemoteID" );
    }*/

    /// @todo ...
    protected function restCall( $method, $url, $payload=array() )
    {
        $options = array( 'method' => $method );

        /// @todo test that ggws is enabled and that there is a server defined
        $results = ggeZWebservicesClient::call( $this->target->attribute( 'server' ), $url, $payload, $options );
        if ( !isset( $results['result'] ) )
        {
            /// @todo settle on an error code
            return -666;
        }
        return $results['result'];
    }

    /**
     * @todo support coalescing of events before sendiong them, eg: a location added then removed
     */
    protected function coalesceEvents( array $events )
    {
        return $events;
    }
}

?>
