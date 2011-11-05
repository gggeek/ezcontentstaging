<?php
/**
 * An eZ REST authentication filter: authenticates using a fixed user ID, but
 * only on some controllers (std code only allows to do that on all controllers at once)
 *
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class contentStagingAutoAuthFilter implements ezpRestRequestFilterInterface
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
            /// @todo take care: what if user does not exist?
            $user = eZUser::fetch( $ini->variable( 'RestAPI', 'UserID' ) );
            $user->loginCurrent();
        }

    }

}

?>
