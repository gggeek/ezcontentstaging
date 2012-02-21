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
            'stagingContent' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id',
                    'eZContentStagingRestContentController',
                    array( 'http-get' => 'load', 'http-put' => 'update', 'http-delete' => 'remove' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId',
                    'eZContentStagingRestContentController',
                    array( 'http-get' => 'load', 'http-put' => 'update', 'http-delete' => 'remove' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentCreate' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects',
                    'eZContentStagingRestContentController',
                    array( 'http-post' => 'create' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentAddVersion' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/versions',
                    'eZContentStagingRestContentController',
                    array( 'http-post' => 'addVersion' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentAddVersionRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/versions',
                    'eZContentStagingRestContentController',
                    array( 'http-post' => 'addVersion' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentPublishVersion' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/versions/:versionNr',
                    'eZContentStagingRestContentController',
                    array( 'http-post' => 'publishVersion' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentPublishVersionRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/versions/:versionNr',
                    'eZContentStagingRestContentController',
                    array( 'http-post' => 'publishVersion' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentAddLocation' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/locations',
                    'eZContentStagingRestContentController',
                    array( 'http-put' => 'addLocation' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentAddLocationRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/locations',
                    'eZContentStagingRestContentController',
                    array( 'http-put' => 'addLocation' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentUpdateSection' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/section',
                    'eZContentStagingRestContentController',
                    array( 'http-put' => 'updateSection' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentUpdateSectionRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/section',
                    'eZContentStagingRestContentController',
                    array( 'http-put' => 'updateSection' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentUpdateStates' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/states',
                    'eZContentStagingRestContentController',
                    array( 'http-put' => 'updateStates' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentUpdateStatesRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/states',
                    'eZContentStagingRestContentController',
                    array( 'http-put' => 'updateStates' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentRemoveLanguage' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/:Id/languages/:language',
                    'eZContentStagingRestContentController',
                    array( 'http-delete' => 'removeLanguage' )
                ),
                $this->getVersionNumber()
            ),
            'stagingContentRemoveLanguageRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/objects/remote/:remoteId/languages/:language',
                    'eZContentStagingRestContentController',
                    array( 'http-delete' => 'removeLanguage' )
                ),
                $this->getVersionNumber()
            ),
            // 'locations'
            'stagingLocationLoad' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/:Id',
                    'eZContentStagingRestLocationController',
                    array( 'http-get' => 'load', 'http-post' => 'hideUnhide', 'http-put' => 'update', 'http-delete' => 'remove' )
                ),
                $this->getVersionNumber()
            ),
            'stagingLocationLoadRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/remote/:remoteId',
                    'eZContentStagingRestLocationController',
                    array( 'http-get' => 'load', 'http-post' => 'hideUnhide', 'http-put' => 'update', 'http-delete' => 'remove' )
                ),
                $this->getVersionNumber()
            ),
            'stagingLocationMove' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/:Id/parent',
                    'eZContentStagingRestLocationController',
                    array( 'http-put' => 'move' )
                ),
                $this->getVersionNumber()
            ),
            'stagingLocationMoveRemote' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/content/locations/remote/:remoteId/parent',
                    'eZContentStagingRestLocationController',
                    array( 'http-put' => 'move' )
                ),
                $this->getVersionNumber()
            ),
            'apiVersionList' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/api/versions',
                    'eZContentStagingRestProviderAnalyzer',
                    array( 'http-get' => 'listVersions' )
                ),
                $this->getVersionNumber()
            ),

            'apiVersion' => new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                    '/api/versions/:version',
                    'eZContentStagingRestProviderAnalyzer',
                    array( 'http-get' => 'describeVersion' )
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