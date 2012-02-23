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
    const VERSIONNUMBER = 1;

    public function getVersionNumber()
    {
        return self::VERSIONNUMBER;
    }

    public function getRoutes()
    {
        return array(

            // "content"
            'stagingContentLoad' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id',
                    'eZContentStagingRestContentController',
                    'load',
                    array(),
                    'http-get'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentLoadRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'eZContentStagingRestContentController',
                    'load',
                    array(),
                    'http-get'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentCreate' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects',
                    'eZContentStagingRestContentController',
                    'create',
                    array(),
                    'http-post'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentAddVersion' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/versions',
                    'eZContentStagingRestContentController',
                    'addVersion',
                    array(),
                    'http-post'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentAddVersionRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/versions',
                    'eZContentStagingRestContentController',
                    'addVersion',
                    array(),
                    'http-post'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentPublishVersion' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/versions/:versionNr',
                    'eZContentStagingRestContentController',
                    'publishVersion',
                    array(),
                    'http-post'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentPublishVersionRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/versions/:versionNr',
                    'eZContentStagingRestContentController',
                    'publishVersion',
                    array(),
                    'http-post'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentUpdate' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id',
                    'eZContentStagingRestContentController',
                    'update',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentUpdateRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'eZContentStagingRestContentController',
                    'update',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentRemove' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id',
                    'eZContentStagingRestContentController',
                    'remove',
                    array(),
                    'http-delete'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentRemoveRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'eZContentStagingRestContentController',
                    'remove',
                    array(),
                    'http-delete'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentAddLocation' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/locations',
                    'eZContentStagingRestContentController',
                    'addLocation',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentAddLocationRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/locations',
                    'eZContentStagingRestContentController',
                    'addLocation',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentUpdateSection' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/section',
                    'eZContentStagingRestContentController',
                    'updateSection',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentUpdateSectionRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/section',
                    'eZContentStagingRestContentController',
                    'updateSection',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentUpdateStates' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/states',
                    'eZContentStagingRestContentController',
                    'updateStates',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentUpdateStatesRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/states',
                    'eZContentStagingRestContentController',
                    'updateStates',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentRemoveLanguage' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/languages/:language',
                    'eZContentStagingRestContentController',
                    'removeLanguage',
                    array(),
                    'http-delete'
                ),
                $this->getVersionNumber()
            ),
            'stagingContentRemoveLanguageRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/languages/:language',
                    'eZContentStagingRestContentController',
                    'removeLanguage',
                    array(),
                    'http-delete'
                ),
                $this->getVersionNumber()
            ),
            // 'locations'
            'stagingLocationLoad' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/:Id',
                    'eZContentStagingRestLocationController',
                    'load',
                    array(),
                    'http-get'
                ),
                $this->getVersionNumber()
            ),
            'stagingLocationLoadRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
                    'load',
                    array(),
                    'http-get'
                ),
                $this->getVersionNumber()
            ),
            'stagingLocationHideUnhide' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/:Id',
                    'eZContentStagingRestLocationController',
                    'hideUnhide',
                    array(),
                    'http-post'
                ),
                $this->getVersionNumber()
            ),
            'stagingLocationHideUnhideRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
                    'hideUnhide',
                    array(),
                    'http-post'
                ),
                $this->getVersionNumber()
            ),
            // update the sort field / sort order or the priority
            // depending on the content of the PUT request
            'stagingLocationUpdate' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/:Id',
                    'eZContentStagingRestLocationController',
                    'update',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingLocationUpdateRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
                    'update',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingLocationMove' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/:Id/parent',
                    'eZContentStagingRestLocationController',
                    'move',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingLocationMoveRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/remote/:remoteId/parent',
                    'eZContentStagingRestLocationController',
                    'move',
                    array(),
                    'http-put'
                ),
                $this->getVersionNumber()
            ),
            'stagingLocationRemove' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/:Id',
                    'eZContentStagingRestLocationController',
                    'remove',
                    array(),
                    'http-delete'
                ),
                $this->getVersionNumber()
            ),
            'stagingLocationRemoveRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
                    'remove',
                    array(),
                    'http-delete'
                ),
                $this->getVersionNumber()
            ),

            // user management (implemented here waiting for REST API to catch up)

            'userCreateSession' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/user/sessions',
                    'eZContentStagingRestUserController',
                    'createSession',
                    array(),
                    'http-post'
                ),
                $this->getVersionNumber()
            ),

            'userDeleteSession' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/user/sessions/:sessionId',
                    'eZContentStagingRestUserController',
                    'deleteSession',
                    array(),
                    'http-delete'
                ),
                $this->getVersionNumber()
            ),

            // helper calls

            'apiVersionList' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/api/versions',
                    'eZContentStagingRestProviderAnalyzer',
                    'listVersions',
                    array(),
                    'http-get'
                ),
                $this->getVersionNumber()
            ),

            'apiVersion' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/api/versions/:version',
                    'eZContentStagingRestProviderAnalyzer',
                    'describeVersion',
                    array(),
                    'http-get'
                ),
                $this->getVersionNumber()
            )
        );
    }

    public function getViewController()
    {
        return new ezpRestApiViewController();
    }
}