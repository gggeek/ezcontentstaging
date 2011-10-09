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

class contentStagingRestApiProvider implements ezpRestProviderInterface
{
    public function getRoutes()
    {
        $routes = array();
        return $routes;
    }

    public function getViewController()
    {
        return new ezpRestApiViewController();
    }
}
