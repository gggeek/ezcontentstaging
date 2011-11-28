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
                    array(),
                    'http-get'
                ),
                1
            ),
            'stagingContentLoadRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'eZContentStagingRestContentController',
                    'load',
                    array(),
                    'http-get'
                ),
                1
            ),
            'stagingContentCreate' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects',
                    'eZContentStagingRestContentController',
                    'create',
                    array(),
                    'http-post'
                ),
                1
            ),
            'stagingContentAddVersion' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id/versions',
                    'eZContentStagingRestContentController',
                    'addVersion',
                    array(),
                    'http-post'
                ),
                1
            ),
            'stagingContentAddVersionRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId/versions',
                    'eZContentStagingRestContentController',
                    'addVersion',
                    array(),
                    'http-post'
                ),
                1
            ),
            'stagingContentPublishVersion' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id/versions/:versionNr',
                    'eZContentStagingRestContentController',
                    'publishVersion',
                    array(),
                    'http-post'
                ),
                1
            ),
            'stagingContentPublishVersionRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId/versions/:versionNr',
                    'eZContentStagingRestContentController',
                    'publishVersion',
                    array(),
                    'http-post'
                ),
                1
            ),
            'stagingContentUpdate' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id',
                    'eZContentStagingRestContentController',
                    'update',
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingContentUpdateRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'eZContentStagingRestContentController',
                    'update',
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingContentRemove' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id',
                    'eZContentStagingRestContentController',
                    'remove',
                    array(),
                    'http-delete'
                ),
                1
            ),
            'stagingContentRemoveRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'eZContentStagingRestContentController',
                    'remove',
                    array(),
                    'http-delete'
                ),
                1
            ),
            'stagingContentAddLocation' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id/locations',
                    'eZContentStagingRestContentController',
                    'addLocation',
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingContentAddLocationRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId/locations',
                    'eZContentStagingRestContentController',
                    'addLocation',
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingContentUpdateSection' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id/section',
                    'eZContentStagingRestContentController',
                    'updateSection',
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingContentUpdateSectionRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId/section',
                    'eZContentStagingRestContentController',
                    'updateSection',
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingContentUpdateStates' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id/states',
                    'eZContentStagingRestContentController',
                    'updateStates',
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingContentUpdateStatesRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId/states',
                    'eZContentStagingRestContentController',
                    'updateStates',
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingContentRemoveLanguage' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/:Id/languages/:Language',
                    'eZContentStagingRestContentController',
                    'removeLanguage',
                    array(),
                    'http-delete'
                ),
                1
            ),
            'stagingContentRemoveLanguageRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/objects/remote/:remoteId/languages/:language',
                    'eZContentStagingRestContentController',
                    'removeLanguage',
                    array(),
                    'http-delete'
                ),
                1
            ),
            // 'locations'
            'stagingLocationLoad' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id',
                    'eZContentStagingRestLocationController',
                    'load',
                    array(),
                    'http-get'
                ),
                1
            ),
            'stagingLocationLoadRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
                    'load',
                    array(),
                    'http-get'
                ),
                1
            ),
            'stagingLocationHideUnhide' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id',
                    'eZContentStagingRestLocationController',
                    'hideUnhide',
                    array(),
                    'http-post'
                ),
                1
            ),
            'stagingLocationHideUnhideRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
                    'hideUnhide',
                    array(),
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
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingLocationUpdateRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
                    'update',
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingLocationMove' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id/parent',
                    'eZContentStagingRestLocationController',
                    'move',
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingLocationMoveRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId/parent',
                    'eZContentStagingRestLocationController',
                    'move',
                    array(),
                    'http-put'
                ),
                1
            ),
            'stagingLocationRemove' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/:Id',
                    'eZContentStagingRestLocationController',
                    'remove',
                    array(),
                    'http-delete'
                ),
                1
            ),
            'stagingLocationRemoveRemote' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
                    'remove',
                    array(),
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
