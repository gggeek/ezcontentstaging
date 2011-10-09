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

    function syncEvents( array $events )
    {
        $results = array();
        foreach( $events as $event )
        {
            $data = $event->getData();
            switch( $event->attribute( 'type' ) )
            {
                case eZContentStagingItemEvent::ACTION_ADDLOCATION:
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    $RemoteParentNodeRemoteID = self::buildRemoteId( $data['parentNodeID'] , $data['parentNodeRemoteID'] );
                    /// @todo test that $RemObjID is not null
                    $method = 'PUT';
                    $url = "/content/objects/remote/$RemoteObjRemoteID/locations?parentRemoteId=$RemoteParentNodeRemoteID";
                    $payload = array(
                        'remoteId' => $RemoteNodeRemoteID,
                        /// @todo transcode values
                        'priority' => $data['priority'],
                        'sortField' => $data['sortField'],
                        'sortOrder' => $data['sortOrder']
                        );
                    $out = $this->restCall( $method, $url, $payload );
                    break;
                case 'delete':
                    ;
                    break;
                case 'hide':
                    $method = 'PUT';
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    $url = "/content/locations?remoteId=$RemoteNodeRemoteID";
                    $payload = array(
                        'hide' => $data['hide'],
                        );
                    $out = $this->restCall( $method, $url, $payload );
                    break;
                case 'move':
                    ;
                    break;
                case 'publish':
                    ;
                    break;
                case eZContentStagingItemEvent::ACTION_REMOVELOCATION:
                    $method = 'DELETE';
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    $url = "/content/locations?remoteId=$RemoteNodeRemoteID";
                    $out = $this->restCall( $method, $url );
                    break;
                case 'removetranslation':
                    ;
                    break;
                case eZContentStagingItemEvent::ACTION_UPDATESECTION:
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $method = 'PUT';
                    $url = "/content/objects/remote/$RemoteObjRemoteID/section?sectionId={$data['sectionID']}";
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
            $results[] = $out;
        }

        return $results;
    }

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
    * @todo this function should possibly be calling a handler for greater flexibility
    */
    static protected function buildRemoteId( $sourceId, $sourceRemoteId, $type='node' )
    {
        return $sourceRemoteId;
    }
}

?>
