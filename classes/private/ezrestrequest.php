<?php
/**
 * Class used to wrap 'REST' requests.
 *
 * @author G. Giunta
 * @copyright (C) 2009-2012 G. Giunta
 */

class eZRESTRequest
{
    /// Contains the request parameters
    protected $Parameters = array();

    protected $Name;

    protected $Verb = 'GET';

    public function __construct( $name = '', $parameters = array() )
    {
        $this->Name = (string)$name;
        $this->Parameters = $parameters;
    }

    /**
     * Returns the request name.
     */
    public function name()
    {
        return $this->Name;
    }

    /// as in 'http verb'. This is not the method name of the request.
    public function method()
    {
        return strtoupper( $this->Verb );
    }

    /**
     * No request body for GET requests, as all params are put in the url
     */
    public function payload()
    {
        switch ( $this->Verb )
        {
            case "GET":
            case "HEAD":
            case "TRACE":
                return '';
        }

        return json_encode( $this->Parameters );
    }

    /**
     * Final part of url that is built REST style: /methodName?p1=val1&p2=val2
     * unless request is POST (or other non-GET), then they are sent as part
     * of body.
     * Note: Flickr uses calls like this: ?method=methodName&p1=val1&p2=val2
     *       Google varies a lot
     */
    public function requestURI( $uri )
    {
        $parsed = parse_url( $uri );

        $return = '';
        if ( isset( $parsed['user'] ) )
        {
            $return .= $parsed['user'] . '@' . $parsed['pass'];
        }
        switch ( $this->Verb )
        {
            case "GET":
            case "HEAD":
            case "TRACE":
                $params = $this->Parameters;
                break;
            default:
                $params = array();
        }

        $return .= rtrim( $parsed['path'], '/' ) . '/' . ltrim( $this->Name, '/' );

        if ( isset( $parsed['query'] ) )
        {
            $return  .= '?' . $parsed['query'];
            $next = '&';
        }
        else
        {
            $next = '?';
        }
        if ( count( $params ) )
        {
            $return .= $next . json_encode( $params );
        }
        if ( isset( $parsed['fragment'] ) )
        {
            $return .= '#' . $parsed['fragment'];
        }

        return $return;
    }

    public function requestHeaders()
    {
        /// shall we declare support for insecure stuff such as php and serialized php?
        return array( 'Accept' => 'application/json, text/xml; q=0.5' );
    }

    // allow easily swapping REST requests from GET to POST and viceversa
    public function setMethod( $method )
    {
        $this->Verb = $method;
    }
}
