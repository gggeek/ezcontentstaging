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
            if ( is_a( $message, 'exception' ) )
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
}

?>