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
            switch( $event->attribute( 'to_sync' ) )
            {
                case eZContentStagingEvent::ACTION_ADDLOCATION:
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

                case eZContentStagingEvent::ACTION_DELETE:
                    $method = 'DELETE';
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $url = "/content/objects/remote/$RemoteObjRemoteID?trash={$data['trash']}";
                    $out = $this->restCall( $method, $url );
                    break;

                case eZContentStagingEvent::ACTION_HIDEUNHIDE:
                    $method = 'POST';
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    $url = "/content/locations?remoteId=$RemoteNodeRemoteID&hide=" . $data['hide'];
                    //$payload = array(
                    //    'hide' => $data['hide'],
                    //    );
                    $out = $this->restCall( $method, $url );
                    break;

                case 'move':
                    ;
                    break;

                case 'publish':
                    ;
                    break;

                case eZContentStagingEvent::ACTION_REMOVELOCATION:
                    $method = 'DELETE';
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    /// @todo transcode value for trash
                    $url = "/content/locations?remoteId=$RemoteNodeRemoteID&trash={$data['trash']}";
                    $out = $this->restCall( $method, $url );
                    break;

                case eZContentStagingEvent::ACTION_REMOVETRANSLATION:
                    $method = 'DELETE';
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $baseurl = "/content/objects/remote/$RemoteObjRemoteID/translations/";
                    foreach ( $data['translations'] as $translation )
                    {
                        /// @todo transcode value for language
                        $url = $baseurl . "...";
                        $out = $this->restCall( $method, $url );
                        if ( $out != 0 )
                        {
                            /// @todo shall we break here or what? we only updated a few priorities, not all of them...
                            break;
                        }
                    }
                    break;

                case eZContentStagingEvent::ACTION_UPDATESECTION:
                    $method = 'PUT';
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $url = "/content/objects/remote/$RemoteObjRemoteID/section?sectionId={$data['sectionID']}";
                    $out = $this->restCall( $method, $url );
                    break;

                case eZContentStagingEvent::ACTION_SORT:
                    $method = 'PUT';
                    $RemoteNodeRemoteID = self::buildRemoteId( $data['nodeID'], $data['nodeRemoteID'] );
                    $url = "/content/locations?remoteId=$RemoteNodeRemoteID";
                    $payload = array(
                        /// @todo transcode values
                        // @todo can we omit safely to send priority?
                        //'priority' => $data['priority'],
                        'sortField' => $data['sortField'],
                        'sortOrder' => $data['sortOrder']
                        );
                    $out = $this->restCall( $method, $url, $payload );
                    break;

                case 'swap':
                    ;
                    break;

                case eZContentStagingEvent::ACTION_UPDATEALWAYSAVAILABLE:
                    $method = 'PUT';
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $baseurl = "/content/objects/remote/$RemoteObjRemoteID";
                    /// @todo transcode values
                    $payload = array( 'alwaysAvailable' => $data['alwaysAvailable'] );
                    $out = $this->restCall( $method, $url, $payload );
                    break;

                case eZContentStagingEvent::ACTION_UPDATEINITIALLANGUAGE:
                    $method = 'PUT';
                    $RemoteObjRemoteID = self::buildRemoteId( $event->attribute( 'object_id' ), $data['objectRemoteID'], 'object' );
                    $baseurl = "/content/objects/remote/$RemoteObjRemoteID";
                    /// @todo transcode values
                    $payload = array( 'initialLanguage' => $data['initialLanguage'] );
                    $out = $this->restCall( $method, $url, $payload );
                    break;

                case eZContentStagingEvent::ACTION_UPDATEMAINASSIGNMENT:
                    // not supported yet, as we have to figure out how to translate this into a set of REST API calls
                    $out = -666;
                    break;

                case eZContentStagingEvent::ACTION_UPDATEPRIORITY:
                    $method = 'PUT';
                    foreach ( $data['priorities'] as $priority )
                    {
                        $RemoteNodeRemoteID = self::buildRemoteId( $priority['nodeID'], $priority['nodeRemoteID'] );
                        $url = "/content/locations?remoteId=$RemoteNodeRemoteID";
                        $payload = array(
                            // @todo can we omit safely to send rest of data?
                            'priority' => $priority['priority']
                        );
                        $out = $this->restCall( $method, $url, $payload );
                        if ( $out != 0 )
                        {
                            /// @todo shall we break here or what? we only updated a few priorities, not all of them...
                            break;
                        }
                    }
                    break;
                default:
                    $out = eZContentStagingEvent::ERROR_EVENTTYPEUNKNOWNTOTRANSPORT; // should we store this code in this class?
            }
            $results[] = $out;
        }

        return $results;
    }

    /// @todo ...
    protected function restCall( $method, $url, $payload=array() )
    {
        $options = array( 'method' => $method, 'requestType' => 'application/json' );

        /// @todo test that ggws is enabled and that there is a server defined
        $results = ggeZWebservicesClient::call( $this->target->attribute( 'server' ), $url, $payload, $options );
        if ( !isset( $results['result'] ) )
        {
            /// @todo settle on an error code. Best would be to decode $results['error'], which we get as a string
            return -999;
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
