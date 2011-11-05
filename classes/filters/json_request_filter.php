<?php
/**
 * An eZ REST request filter: decodes PUT/POST requests with json payloads, but
 * only on some controllers
 *
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class contentStagingJsonRequestFilter implements ezpRestRequestFilterInterface
{
    protected $request;
    protected $controllerClass;

    public function __construct( ezcMvcRoutingInformation $routeInfo, ezcMvcRequest $request )
    {
        $this->request = $request;
        $this->controllerClass = $routeInfo->controllerClass;
    }

    public function filter()
    {
        $ini = eZINI::instance( 'contentstagingtarget.ini' );
        $controllers = $ini->variable( 'RestAPI', 'ControllerClasses' );
        if ( in_array( $this->controllerClass, $controllers ) )
        {
            $headers = $this->request->raw;

            if ( isset( $headers['CONTENT_TYPE'] ) && $headers['CONTENT_TYPE'] == 'application/json'
                && ( $this->request->protocol === 'http-post' || $this->request->protocol === 'http-put' ) )
            {
                $variables = json_decode( $this->request->body, true );
                if ( is_array( $variables ) )
                {
                    $this->request->inputVariables = $variables;
                }
            }
        }
    }

}

?>
