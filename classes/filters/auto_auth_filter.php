<?php
/**
 * An eZ REST authentication filter: authenticates using a fixed user ID, but
 * only on some controllers (std code only allows to do that on all controllers at once,
 * so we can not use its feature of using auth filters, even though doing the things
 * this way has the downside of generating session cookies twice )
 *
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZContentStagingAutoAuthFilter implements ezpRestRequestFilterInterface
{
    protected $controllerClass;

    public function __construct( ezcMvcRoutingInformation $routeInfo, ezcMvcRequest $request )
    {
        $this->controllerClass = $routeInfo->controllerClass;
    }

    public function filter()
    {
        $ini = eZINI::instance( 'contentstagingtarget.ini' );
        $controllers = $ini->variable( 'RestAPI', 'ControllerClasses' );
        if ( in_array( $this->controllerClass, $controllers ) )
        {
            $userId = $ini->variable( 'RestAPI', 'UserID' );
            $currentUser = eZUser::currentUser();
            if ( $currentUser->attribute( 'contentobject_id' ) != $userId )
            {
                /// @todo take care: what if user does not exist?
                $user = eZUser::fetch( $userId );

                /// @todo look up if there is a way to log given current user without
                ///       generating a session
                $user->loginCurrent();
            }
        }
    }

}

?>