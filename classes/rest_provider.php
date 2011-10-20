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
        $routes = array(
            'stagingRemove' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'contentStagingRestContentController',
                    'remove',
                    'http-delete'
                ),
                1
            ),
            'stagingAddLocation' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId/locations',
                    'contentStagingRestContentController',
                    'addLocation',
                    'http-put'
                ),
                1
            ),
        );
        return $routes;
    }

    public function getViewController()
    {
        return new ezpRestApiViewController();
    }
}
