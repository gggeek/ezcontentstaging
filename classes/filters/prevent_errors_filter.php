<?php
/**
 * An eZ REST request filter: it turns off echoing to screen of php warnings,
 * which are generated, by code executed as rest service, when debugOutput is on
 * and ezdebug::writeXXX is called.
 * More important, it also prevents copde from halting when ezdebug::writeError
 * is called.
 *
 * @package ezcontentstaging
 *
 * @author
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZContentStagingPreventErrorsFilter implements ezpRestRequestFilterInterface
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
            $errorhandling = eZDebug::setHandleType( eZDebug::HANDLE_FROM_PHP );
        }
    }

}

?>