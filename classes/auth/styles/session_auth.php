<?php
/**
 * File containing ezpRestSessionAuthStyle class
 *
 * @package ezcontentstaging
 *
 * @author
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

/**
 * This auth style is used when eZ session (cookie based ) authentication is required for current REST request
 *
 * @todo add support for NOT letting in anonymous user
 */
class ezpRestSessionAuthStyle extends ezpRestAuthenticationStyle implements ezpRestAuthenticationStyleInterface
{
    /**
     * @see ezpRestAuthenticationStyleInterface::setup()
     */
    public function setup( ezcMvcRequest $request )
    {
        return null;
    }

    /**
     * To avoid creating a new session for already logged-in users, we need to return NULL
     * @see ezpRestAuthConfiguration::filter()
     * @see ezpRestAuthenticationStyleInterface::authenticate()
     */
    public function authenticate( ezcAuthentication $auth, ezcMvcRequest $request )
    {
        // this call makes all the authentication we need - it does not start a session if no session cookie is received
        eZSession::lazyStart();

        // since we will be returning NULL, ezpRestAuthConfiguration will not
        // call setUser on us. We do it by ourselves
        $userID = eZSession::issetkey( 'eZUserLoggedInID', false ) ? eZSession::get( 'eZUserLoggedInID' ) : false;
        if ( $userID )
        {
            $user = eZUser::fetch( $userID );
            if ( !$user )
            {
                throw new ezpUserNotFoundException( $userID );
            }
            $this->setuser( $user );
        }

        return null;
    }
}
