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
    protected static function errorResult( $code, $message )
    {
        $result = new ezpRestMvcResult();
        $result->status = new ezpRestHttpResponse( $code, $message );
        return $result;
    }
}

?>