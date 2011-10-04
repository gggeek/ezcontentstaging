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
        return -100;

        foreach( $item->attribute( 'events' ) as $event )
        {
            switch( $event->type )
            {
                case eZContentStagingItemEvent::ACTION_ADDLOCATION:
                    $data = $event->getData();
                    $RemObjID = $this->getRemObjID( $data['objectRemoteId'] );
                    $method = 'PUT';
                    $url = "/content/objects/$RemObjID/locations?parentRemoteId={$data['parentRemoteId']}";
                    $body = array(
                        /// @todo transcode values
                        'priority' => $data['priority'],
                        'remoteId' => $data['remoteId'],
                        'sortField' => $data['sortField'],
                        'sortOrder' => $data['sortOrder']
                        );
                    $out = $this->restCall( $url, $body, $method );
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
                    $method = 'DELETE';
                    $url = "/content/locations?remoteId={$data['remoteId']}";
                    $out = $this->restCall( $url, null, $method );
                    break;
                case 'removetranslation':
                    ;
                    break;
                case 'section':
                    ;
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
    }

    protected function getRemObjID( $RemObjRemoteID )
    {
        $out = $this->restCall( "/content/objects?remoteId=$RemObjRemoteID" );
    }

    protected function restCall( $url, $payload = null, $method='GET' )
    {
        $payload = json_encode( $payload );
    }
}

?>
