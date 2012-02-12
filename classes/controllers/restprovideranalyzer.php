<?php
/**
 * A class used to introspect REST API providers and be the base for HATEOAS.
 *
 * Currently it hardcodes analysis of the EZCS provider only, but it could be made
 * generic in teh future
 *
 * @version $Id$
 * @copyright 2012
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
        foreach( $provider->getRoutes() as $route )
        {
            if ( is_a( $route, 'ezpRestInspectableRoute' ) )
            {
                $versions[] = $route->getVersion();
            }
        }

        $result = new ezpRestMvcResult();
        $result->variables = array_unique( $versions );
        return $result;
    }

    /**
     * Describes all routes for a gievn API version.
     * @todo return also a filed describing the type of route?
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
        foreach( $ini->variable( 'ApiProvider', 'ProviderClass' ) as $prefix => $class )
        {
            if ( $class == $providerClass )
            {
                $patternprefix = $prefix . '/';
                break;
            }
        }


        $provider = new $providerClass();
        foreach( $provider->getRoutes() as $name => $route )
        {
            if ( is_a( $route, 'ezpRestInspectableRoute' ) )
            {
                if ( $route->getVersion() == $this->version )
                {
                    $routes[$name] = array(
                        'urlpattern' => $patternprefix . $route->getPattern(),
                        'verb' => $route->getVerb(),
                        'description' => $route->getDescription()
                    );
                }
            }
        }

        $result = new ezpRestMvcResult();
        $result->variables = $routes;
        return $result;
    }
}

?>