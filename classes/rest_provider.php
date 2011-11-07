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

class eZContentStagingRestApiProvider implements ezpRestProviderInterface
{
    public function getRoutes()
    {
        return array(

            // "content"
            'stagingContentLoad' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id',
                    'eZContentStagingRestContentController',
                    'load',
                    'http-get'
                ),
                1
            ),
            'stagingContentLoadRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'eZContentStagingRestContentController',
                    'load',
                    'http-get'
                ),
                1
            ),
            'stagingContentCreate' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects',
                    'eZContentStagingRestContentController',
                    'create',
                    'http-post'
                ),
                1
            ),
            'stagingContentUpdate' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id',
                    'eZContentStagingRestContentController',
                    'update',
                    'http-put'
                ),
                1
            ),
            'stagingContentUpdateRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'eZContentStagingRestContentController',
                    'update',
                    'http-put'
                ),
                1
            ),
            'stagingContentRemove' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id',
                    'eZContentStagingRestContentController',
                    'remove',
                    'http-delete'
                ),
                1
            ),
            'stagingContentRemoveRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'eZContentStagingRestContentController',
                    'remove',
                    'http-delete'
                ),
                1
            ),
            'stagingContentAddLocation' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id/locations',
                    'eZContentStagingRestContentController',
                    'addLocation',
                    'http-put'
                ),
                1
            ),
            'stagingContentAddLocationRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId/locations',
                    'eZContentStagingRestContentController',
                    'addLocation',
                    'http-put'
                ),
                1
            ),

            // 'locations'
            'stagingLocationLoad' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id',
                    'eZContentStagingRestLocationController',
                    'load',
                    'http-get'
                ),
                1
            ),
            'stagingLocationLoadRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
                    'load',
                    'http-get'
                ),
                1
            ),
            'stagingLocationHideUnhide' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id',
                    'eZContentStagingRestLocationController',
                    'hideUnhide',
                    'http-post'
                ),
                1
            ),
            'stagingLocationHideUnhideRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
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
                    'eZContentStagingRestLocationController',
                    'update',
                    'http-put'
                ),
                1
            ),
            'stagingLocationUpdateRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
                    'update',
                    'http-put'
                ),
                1
            ),
            'stagingLocationMove' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id/parent',
                    'eZContentStagingRestLocationController',
                    'move',
                    'http-put'
                ),
                1
            ),
            'stagingLocationMoveRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId/parent',
                    'eZContentStagingRestLocationController',
                    'move',
                    'http-put'
                ),
                1
            ),
            'stagingLocationRemove' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id',
                    'eZContentStagingRestLocationController',
                    'remove',
                    'http-delete'
                ),
                1
            ),
            'stagingLocationRemoveRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
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
