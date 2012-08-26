<?php
/**
 * A class used to introspect REST API providers and be the base for HATEOAS.
 *
 * Currently it hardcodes analysis of the EZCS provider only, but it could be made
 * generic in teh future
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZContentStagingRestProviderAnalyzer extends ezpRestMvcController
{
    protected function getProviderClass()
    {
        return 'eZContentStagingRestApiProvider';
    }

    /**
     * Lists all existing versions of the given API by checking version nr for every route.
     * @todo what about non-versioned routes?
     */
    public function doListVersions()
    {
        $versions = array();

        $provider = $this->getProviderClass();
        $provider = new $provider();
        // use a dynamic way to build the href to "versions/x" by finding the route that maps to eZContentStagingRestProviderAnalyzer::doDescribeVersion
        $versionDescribeRoute = false;
        foreach ( $provider->getRoutes() as $route )
        {
            if ( is_a( $route, 'ezpRestInspectableRoute' ) )
            {
                $versions[] = $route->getVersion();
                if ( $route->getControllerClassName() == 'eZContentStagingRestProviderAnalyzer' &&
                    $route->getAction() == 'describeVersion' )
                {
                    $versionDescribeRoute = $route;
                }
            }
        }

        $patternprefix = '';
        if ( $versionDescribeRoute )
        {
            // Work around the fact that a route does not know which provider it has
            // been bound to
            $providerClass = $this->getProviderClass();

            $ini = eZINI::instance( 'rest.ini' );
            foreach ( $ini->variable( 'ApiProvider', 'ProviderClass' ) as $prefix => $class )
            {
                if ( $class == $providerClass )
                {
                    $patternprefix = $prefix . '/';
                    break;
                }
            }
            $patternprefix .= $versionDescribeRoute->getPattern();
        }

        $out = array();
        foreach ( array_unique( $versions ) as $version )
        {
            $out[] = array( 'version' => $version, 'href' => str_replace( '/:version', "/$version", $patternprefix ) );
        }

        $result = new ezpRestMvcResult();
        $result->variables = $out;
        return $result;
    }

    /**
     * Describes all routes for a gievn API version.
     * Format used: @see https://github.com/mashery/iodocs
     * @todo return also a field describing the type of route?
     * @todo return a 404 if version does not exist at all
     */
    public function doDescribeVersion()
    {
        $routes = array();

        $providerClass = $this->getProviderClass();

        // Work around the fact that a route does not know which provider it has
        // been bound to
        $patternprefix = '';
        $ini = eZINI::instance( 'rest.ini' );
        foreach ( $ini->variable( 'ApiProvider', 'ProviderClass' ) as $prefix => $class )
        {
            if ( $class == $providerClass )
            {
                $patternprefix = $prefix . '/';
                break;
            }
        }


        $provider = new $providerClass();
        foreach ( $provider->getRoutes() as $name => $route )
        {
            if ( is_a( $route, 'ezpRestInspectableRoute' ) )
            {
                if ( $route->getVersion() == $this->version )
                {
                    $routes[] = array(
                        'MethodName' => $name,
                        /// @todo get params from route (embedded ones) + action (query string ones)
                        'parameters' => array(),
                        /// @todo according to docs, we could remove the prefix part from the url (and put it in the "publicPath"
                        'URI' => $patternprefix . $route->getPattern(),
                        'HTTPMethod' => strtoupper( $route->getVerb() ),
                        'Synopsis' => $route->getDescription()
                    );
                }
            }
        }

        $result = new ezpRestMvcResult();
        $result->variables = array( 'endpoints' => array( array( 'name' => $prefix, 'methods' => $routes ) ) );
        return $result;
    }
}
