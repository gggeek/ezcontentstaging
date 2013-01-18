<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZContentStagingRestApiProvider implements ezpRestProviderInterface
{
    const VERSIONNUMBER = 1;

    public function getVersionNumber()
    {
        return self::VERSIONNUMBER;
    }

    static $routesDefinition = array(

        // content
        'stagingContent' => array(
            '/content/objects/:Id',
            'eZContentStagingRestContentController',
            array( 'http-get' => 'load', 'http-put' => 'update', 'http-delete' => 'remove' )
        ),
        'stagingContentRemote' => array(
            '/content/objects/remote/:remoteId',
            'eZContentStagingRestContentController',
            array( 'http-get' => 'load', 'http-put' => 'update', 'http-delete' => 'remove' )
        ),
        'stagingContentCreate' => array(
            '/content/objects',
            'eZContentStagingRestContentController',
            array( 'http-post' => 'create' )
        ),
        'stagingContentAddVersion' => array(
            '/content/objects/:Id/versions',
            'eZContentStagingRestContentController',
            array( 'http-post' => 'addVersion' )
        ),
        'stagingContentAddVersionRemote' => array(
            '/content/objects/remote/:remoteId/versions',
            'eZContentStagingRestContentController',
            array( 'http-post' => 'addVersion' )
        ),
        'stagingContentPublishVersion' => array(
            '/content/objects/:Id/versions/:versionNr',
            'eZContentStagingRestContentController',
            array( 'http-post' => 'publishVersion' )
        ),
        'stagingContentPublishVersionRemote' => array(
            '/content/objects/remote/:remoteId/versions/:versionNr',
            'eZContentStagingRestContentController',
            array( 'http-post' => 'publishVersion' )
        ),
        'stagingContentAddLocation' => array(
            '/content/objects/:Id/locations',
            'eZContentStagingRestContentController',
            array( 'http-put' => 'addLocation' )
        ),
        'stagingContentAddLocationRemote' => array(
            '/content/objects/remote/:remoteId/locations',
            'eZContentStagingRestContentController',
            array( 'http-put' => 'addLocation' )
        ),
        'stagingContentUpdateSection' => array(
            '/content/objects/:Id/section',
            'eZContentStagingRestContentController',
            array( 'http-put' => 'updateSection' )
        ),
        'stagingContentUpdateSectionRemote' => array(
            '/content/objects/remote/:remoteId/section',
            'eZContentStagingRestContentController',
            array( 'http-put' => 'updateSection' )
        ),
        'stagingContentUpdateStates' => array(
            '/content/objects/:Id/states',
            'eZContentStagingRestContentController',
            array( 'http-put' => 'updateStates' )
        ),
        'stagingContentUpdateStatesRemote' => array(
            '/content/objects/remote/:remoteId/states',
            'eZContentStagingRestContentController',
            array( 'http-put' => 'updateStates' )
        ),
        'stagingContentRemoveLanguage' => array(
            '/content/objects/:Id/languages/:language',
            'eZContentStagingRestContentController',
            array( 'http-delete' => 'removeLanguage' )
        ),
        'stagingContentRemoveLanguageRemote' => array(
            '/content/objects/remote/:remoteId/languages/:language',
            'eZContentStagingRestContentController',
            array( 'http-delete' => 'removeLanguage' )
        ),

        // 'locations'
        'stagingLocationLoad' => array(
            '/content/locations/:Id',
            'eZContentStagingRestLocationController',
            array( 'http-get' => 'load', 'http-post' => 'hideUnhide', 'http-put' => 'update', 'http-delete' => 'remove', 'http-swap' => 'swap' )
        ),
        'stagingLocationLoadRemote' => array(
            '/content/locations/remote/:remoteId',
            'eZContentStagingRestLocationController',
            array( 'http-get' => 'load', 'http-post' => 'hideUnhide', 'http-put' => 'update', 'http-delete' => 'remove', 'http-swap' => 'swap' )
        ),
        'stagingLocationMove' => array(
            '/content/locations/:Id/parent',
            'eZContentStagingRestLocationController',
            array( 'http-put' => 'move' )
        ),
        'stagingLocationMoveRemote' => array(
            '/content/locations/remote/:remoteId/parent',
            'eZContentStagingRestLocationController',
            array( 'http-put' => 'move' )
        ),

        // helper calls
        'apiVersionList' => array(
            '/api/versions',
            'eZContentStagingRestProviderAnalyzer',
            array( 'http-get' => 'listVersions' )
        ),
        'apiVersion' => array(
            '/api/versions/:version',
            'eZContentStagingRestProviderAnalyzer',
            array( 'http-get' => 'describeVersion' )
        ),
        'ping' => array(
            '/ping',
             'eZContentStagingRestProviderPing',
             array( 'http-get' => 'ping' )
        )
    );

    /**
    * We cope with different evolutions in the REST API lifetime
    */
    public function getRoutes()
    {
        if ( ( eZPublishSDK::majorVersion() + eZPublishSDK::minorVersion() * 0.1 >= 4.7 ) ||
             ( ( eZPublishSDK::majorversion() >= 2012 ) && version_compare( eZPublishSDK::majorversion().'.'.eZPublishSDK::minorversion(), '2012.2' ) >= 0 ) )
        {
            return $this->getRoutes47();
        }
        else
        {
            return $this->getRoutes46();
        }
    }

    /// we should use late static binding here. Waiting for deprecating php 5.2...
    protected function getRoutes46()
    {
        $out = array();
        foreach( self::$routesDefinition as $routeName => $routeDef )
        {
            foreach( $routeDef[2] as $verb => $method )
            {
                $out[ $routeName . ucfirst( substr( $verb, 5 ) ) ] = new ezpRestInspectableVersionedRoute(
                    new ezpRestInspectableRailsRoute(
                        $routeDef[0],
                        $routeDef[1],
                        $method,
                        array(),
                        $verb
                    ),
                    $this->getVersionNumber()
                );
            }
        }
        return $out;
    }

    /// we should use late static binding here. Waiting for deprecating php 5.2...
    protected function getRoutes47()
    {
        $out = array();
        foreach( self::$routesDefinition as $routeName => $routeDef )
        {
            $out[ $routeName ] = new ezpRestInspectableVersionedRoute(
                new ezpRestInspectableRailsRoute(
                     $routeDef[0],
                     $routeDef[1],
                     $routeDef[2]
                ),
                $this->getVersionNumber()
            );
        }
        return $out;
    }

    public function getViewController()
    {
        return new ezpRestApiViewController();
    }
}
