<?php
/**
* @package ezcontentstaging
*
* @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/


class eZContentStagingRestBaseController extends ezpRestMvcController
{
    /**
     * Returns an ezpRestMvcResult with the given http code and (error) message
     * if $message is an object instead of a string, its class name is returned along with 500 internal error
     */
    protected static function errorResult( $code, $message )
    {
        $result = new ezpRestMvcResult();
        if ( is_object( $message) )
        {
            if ( $message instanceof Exception )
            {
                $message = $message->getMessage();
            }
            else
            {
                $code = 500;
                $message = "Method returned an object: " . get_class( $message );
            }
        }
        $result->status = new ezpRestHttpResponse( $code, $message );
        return $result;
    }

    /**
    * A stub helping us to cope with different evolutions in the REST API lifetime
    * @todo take into account also eZCP versions
    */
    protected function getRequestVariables()
    {
        if ( ( version_compare( eZPublishSDK::version(), '4.7.0' ) >= 0 ) ||
             ( eZPublishSDK::majorversion() >= 2012 && version_compare( eZPublishSDK::majorversion().'.'.eZPublishSDK::minorversion(), '2012.2' ) >= 0 ) )
        {
            return $this->request->getParsedBody();
        }
        else
        {
            return $this->request->inputVariables;
        }
    }
}
