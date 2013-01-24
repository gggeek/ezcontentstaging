<?php
/**
 * File containing ezpRestSessionAuthStyle class
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

/**
 * This auth style is used when eZ session (cookie based ) authentication is required for current REST request
 *
 * NB: EXPERIMENTAL. THIS PLUGIN KILLS KITTENS.
 *
 * Known problems:
 * . no mitigation against xsrf (should integrate with ezformtoken)
 * . logs out user when an invalid url is requested
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
        //return null;

        // this call makes all the authentication we need - it does not start a session if no session cookie is received
        eZSession::lazyStart();
        $userID = eZSession::issetkey( 'eZUserLoggedInID', false ) ? eZSession::get( 'eZUserLoggedInID' ) : false;

        $auth = new ezcAuthentication( new ezcAuthenticationIdCredentials( $userID ) );
        /// @todo add a new auth filter that does what we want, ie.
        /// similar to ezpNativeUserAuthFilter but not checking anything when $userID is null
        //$auth->addFilter( new ezpSessionUserAuthFilter() );
        return $auth;
    }

    /**
     * To avoid creating a new session for already logged-in users, we need to return NULL
     * @see ezpRestAuthConfiguration::filter()
     * @see ezpRestAuthenticationStyleInterface::authenticate()
     */
    public function authenticate( ezcAuthentication $auth, ezcMvcRequest $request )
    {
        // since we will be returning NULL, ezpRestAuthConfiguration will not
        // call setUser on us. We do it by ourselves
        $userID = (int)$auth->credentials->id;
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
