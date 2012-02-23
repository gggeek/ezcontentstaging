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

class eZContentStagingRestUserController extends eZContentStagingRestBaseController
{

    // *** rest actions ***

    /**
     * Handle calls for user login
     *
     * Requests:
     * - POST /user/sessions
     */
    public function doCreateSession()
    {
        /// @todo ...
    }

    /**
     * Handle calls for user logout
     *
     * Requests:
     * - DELETE /user/sessions/<sessionId>
     */
    public function doDeleteSession()
    {
        // check that given session id corresponds to current user session id
        if ( session_id() != $this->sessionId )
        {
            return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Session '{$this->sessionId}' not found" );
        }
        else
        {
            // logout user
            // nb: this method also clears basket and regenerates session id,
            // which are most likely useless actions in this context...
            eZUser::logoutCurrent();

            $result = new ezpRestMvcResult();
            $result->status = new ezpRestHttpResponse( 204 );
            return $result;
        }
    }

}
