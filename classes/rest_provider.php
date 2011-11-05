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
        return array(

            // "content"
            'stagingContentLoad' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id',
                    'contentStagingRestContentController',
                    'load',
                    'http-get'
                ),
                1
            ),
            'stagingContentLoadRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'contentStagingRestContentController',
                    'load',
                    'http-get'
                ),
                1
            ),
            'stagingContentCreate' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects',
                    'contentStagingRestContentController',
                    'create',
                    'http-post'
                ),
                1
            ),
            'stagingContentUpdate' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id',
                    'contentStagingRestContentController',
                    'update',
                    'http-put'
                ),
                1
            ),
            'stagingContentUpdateRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'contentStagingRestContentController',
                    'update',
                    'http-put'
                ),
                1
            ),
            'stagingContentRemove' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id',
                    'contentStagingRestContentController',
                    'remove',
                    'http-delete'
                ),
                1
            ),
            'stagingContentRemoveRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'contentStagingRestContentController',
                    'remove',
                    'http-delete'
                ),
                1
            ),
            'stagingContentAddLocation' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id/locations',
                    'contentStagingRestContentController',
                    'addLocation',
                    'http-put'
                ),
                1
            ),
            'stagingContentAddLocationRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId/locations',
                    'contentStagingRestContentController',
                    'addLocation',
                    'http-put'
                ),
                1
            ),

            // 'locations'
            'stagingLocationLoad' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id',
                    'contentStagingRestLocationController',
                    'load',
                    'http-get'
                ),
                1
            ),
            'stagingLocationLoadRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'contentStagingRestLocationController',
                    'load',
                    'http-get'
                ),
                1
            ),
            'stagingLocationHideUnhide' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id',
                    'contentStagingRestLocationController',
                    'hideUnhide',
                    'http-post'
                ),
                1
            ),
            'stagingLocationHideUnhideRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'contentStagingRestLocationController',
                    'hideUnhide',
                    'http-post'
                ),
                1
            ),
            // update the sort field / sort order or the priority
            // depending on the content of the PUT request
            'stagingLocationUpdate' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id',
                    'contentStagingRestLocationController',
                    'update',
                    'http-put'
                ),
                1
            ),
            'stagingLocationUpdateRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'contentStagingRestLocationController',
                    'update',
                    'http-put'
                ),
                1
            ),
            'stagingLocationMove' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id/parent',
                    'contentStagingRestLocationController',
                    'move',
                    'http-put'
                ),
                1
            ),
            'stagingLocationMoveRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId/parent',
                    'contentStagingRestLocationController',
                    'move',
                    'http-put'
                ),
                1
            ),
            'stagingLocationRemove' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id',
                    'contentStagingRestLocationController',
                    'remove',
                    'http-delete'
                ),
                1
            ),
            'stagingLocationRemoveRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'contentStagingRestLocationController',
                    'remove',
                    'http-delete'
                ),
                1
            )

        );
    }

    public function getViewController()
    {
        return new ezpRestApiViewController();
    }
}
