<?php
/**
 * Class used to communicate with 'REST' servers
 *
 * @author G. Giunta
 * @copyright (C) 2009-2012 G. Giunta
 */

class eZRESTClient
{
    const ERROR_MISSING_CURL = -101;
    const ERROR_CANNOT_CONNECT = -102;
    const ERROR_CANNOT_WRITE = -103;
    const ERROR_NO_DATA = -104;
    const ERROR_BAD_RESPONSE = -105; // means response invalid according to http protocol
    const ERROR_TIMEOUT = -106;
    const ERROR_NON_200_RESPONSE = -107; // means response status line is ok but not 20x

    /// The name or IP of the server to communicate with
    protected $Server;
    /// The path to the server
    protected $Path;
    /// The port of the server to communicate with.
    protected $Port;
    /// How long to wait for the call.
    protected $Timeout = 0;
    /// HTTP login for HTTP authentification
    protected $Login;
    /// HTTP password for HTTP authentification
    protected $Password;
    /// @see CURLOPT_AUTH for values
    protected $AuthType = 1;
    protected $Protocol = 'http';
    protected $ForceCURL = false;
    protected $RequestCompression = '';
    protected $Proxy = '';
    protected $ProxyPort = 0;
    protected $ProxyLogin = '';
    protected $ProxyPassword = '';
    protected $ProxyAuthType = 1;
    protected $AcceptedCompression = '';
    protected $SSLVerifyPeer = true;
    protected $SSLVerifyHost = 1;
    protected $SSLCAInfo = '';

    // below here: yet to be used...
    protected $CertPass = '';
    protected $CACert = '';
    protected $CACertDir = '';
    protected $Key = '';
    protected $KeyPass = '';
    protected $KeepAlive = true;

    protected $errorString = '';
    protected $errorNumber = 0;

    protected $RequestHeaders = array();

    // if the user sets this via a
    // setOption call, we will later inject it into the request object
    protected $Verb = null;

    protected $UserAgent = 'eZ REST client';

    /**
     * Creates a new client.
     * @param int|string $port 'ssl' can be used for https connections, or port number
     * @param string $protocol use 'https' (or bool true, for backward compatibility) to specify https connections
     * @todo add a simplfied syntax for constructor, using parse_url and a single string
     */
    public function __construct( $server, $path = '/', $port = 80, $protocol = null )
    {
        // backward compat with ezsoap client
        if ( $protocol === true )
        {
            $protocol = 'https';
        }
        $this->Login = '';
        $this->Password = '';
        $this->Server = $server;
        if ( $path == '' || $path[0] != '/' )
        {
            $path = '/' . $path;
        }
        $this->Path = $path;
        $this->Port = $port;
        // this assumes 0 is a valid port. Weird, but useful for unix domain sockets..
        if ( is_numeric( $port ) )
            $this->Port = (int)$port;
        else if ( strtolower( $port ) == 'ssl' || $protocol == 'https' )
            $this->Port = 443;
        else
            $this->Port = 80;
        if ( $protocol == null )
        {
            // lame, but we know no better
            if ( $this->Port == 443 )
            {
                $this->Protocol = 'https';
            }
            else
            {
                $this->Protocol = 'http';
            }
        }
        else
        {
            $this->Protocol = $protocol;
        }

        if ( $this->Protocol == 'https' && !in_array( 'ssl', stream_get_transports() ) )
        {
            $this->ForceCURL = true;
        }

        // if ZLIB is enabled, let the client by default accept compressed responses
        if ( function_exists( 'gzinflate' ) || (
            function_exists( 'curl_init' ) && ( ( $info = curl_version() ) &&
            ( ( is_string( $info ) && strpos( $info, 'zlib' ) !== null ) || isset( $info['libz_version'] ) ) ) ) )
        {
            $this->AcceptedCompression = 'gzip, deflate';
        }
    }

    /**
     * Sends a request and returns the response object. 0 on error
     */
    protected function sendRequest( $request )
    {
        if ( $this->Verb != '' )
        {
            $request->setMethod( $this->Verb );
        }
        if ( $this->Proxy != '' )
        {
            $connectServer = $this->Proxy;
            $connectPort = $this->ProxyPort;
        }
        else
        {
            $connectServer = $this->Server;
            $connectPort = $this->Port;
            if ( $this->Protocol == 'https' )
            {
                $connectServer = 'ssl://' . $connectServer;
            }
        }

        $this->errorString = '';
        $this->errorNumber = 0;

        // we default to NOT using cURL if not asked to (or if it is not there)
        $useCURL = $this->ForceCURL && in_array( "curl", get_loaded_extensions() );
        if ( !$useCURL )
        {
            if ( $this->ForceCURL )
            {
                $this->errorNumber = self::ERROR_MISSING_CURL;
                $this->errorString = "Error: could not send the request. CURL not installed.";
                return 0;
            }

            // generate payload before opening socket, for a smaller connection time
            $HTTPRequest = $this->payload( $request );

            /// @todo add ssl support with raw sockets
            $fp = stream_socket_client(
                "tcp://" . $connectServer . ":" . $connectPort,
                $this->errorNumber,
                $this->errorString,
                $this->Timeout != 0 ? $this->Timeout : ini_get( "default_socket_timeout" )
            );

            if ( $fp === false )
            {
                // nb: can we feed back to end user the error codes from fsockopen?
                // They come basically from errno.h on unix - http://www.finalcog.com/c-error-codes-include-errno
                // OR WSAGetLastError() on windows - http://msdn.microsoft.com/en-us/library/ms740668(VS.85).aspx
                // this makes them very unreliable to test using either numeric values or constants
                // so we just dump them into the error string, and use a small list
                // of error codes defined locally in the client class
                $this->errorString = $this->errorNumber . ' - ' . $this->errorString;
                $this->errorNumber = self::ERROR_CANNOT_CONNECT;
                return 0;
            }

            if ( $this->Timeout != 0 )
            {
                stream_set_timeout( $fp, $this->Timeout );
            }

            if ( !fwrite( $fp, $HTTPRequest, strlen( $HTTPRequest ) ) )
            {
                fclose( $fp );
                $this->errorNumber = self::ERROR_CANNOT_WRITE;
                $this->errorString = "Error: could not send the request. Could not write to the socket.";
                return 0;
            }

            // fetch the response
            $rawResponse = stream_get_contents( $fp );
            $info = stream_get_meta_data( $fp );

            // close the socket
            fclose( $fp );

            if ( $info['timed_out'] )
            {
                $this->errorNumber = self::ERROR_TIMEOUT;
                $this->errorString = "Error: could not receive the response. Timeout while reading from socket.";
                return 0;
            }
        }
        else
        {
            if ( ( $this->Protocol == 'http' && $this->Port == 80 ) || ( $this->Protocol == 'https' && $this->Port == 443 ) )
            {
                $port = '';
            }
            else
            {
                $port = ':' . $this->Port;
            }
            $URL = $this->Protocol . "://" . $this->Server . $port . $request->requestURI( $this->Path );
            $ch = curl_init ( $URL );
            if ( $ch != 0 )
            {
                curl_setopt( $ch, CURLOPT_HEADER, 1 );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

                if ( $this->Timeout != 0 )
                {
                    curl_setopt( $ch, CURLOPT_TIMEOUT, $this->Timeout );
                }

                if ( $this->Proxy != '' )
                {
                    curl_setopt( $ch, CURLOPT_PROXY, $this->Proxy . ':' . $this->ProxyPort );
                    if ( $this->ProxyLogin != '' )
                    {
                        curl_setopt( $ch, CURLOPT_PROXYUSERPWD, $this->ProxyLogin . ':' . $this->ProxyPassword );
                        /// @todo check curl version: need 7.10.7 min for CURLOPT_PROXYAUTH
                        curl_setopt( $ch, CURLOPT_PROXYAUTH, $this->ProxyAuthType );
                    }
                }

                if ( $this->login() != "" )
                {
                    curl_setopt( $ch, CURLOPT_USERPWD, $this->login() . ':' . $this->password() );
                    curl_setopt( $ch, CURLOPT_HTTPAUTH, $this->AuthType );
                }

                if ( $this->UserAgent != '' )
                {
                    curl_setopt( $ch, CURLOPT_USERAGENT, $this->UserAgent );
                }

                if ( $this->AcceptedCompression != '' )
                {
                    /// @todo check curl version: need 7.10 for CURLOPT_ENCODING
                    curl_setopt( $ch, CURLOPT_ENCODING, $this->AcceptedCompression );
                }

                list( $verb, $headers, $payload ) = $this->payload( $request, true );

                if ( $payload != '' )
                {
                    //curl_setopt( $ch, CURLOPT_POST, true );
                    curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
                }
                if ( $verb != 'GET' && $verb != 'POST' )
                {
                    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $verb );
                }
                curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

                if ( $this->Protocol == 'https' )
                {
                    // whether to verify cert's common name (CN); 0 for no, 1 to verify that it exists, and 2 to verify that it matches the hostname used
                    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, $this->SSLVerifyHost );
                    // whether to verify remote host's cert
                    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $this->SSLVerifyPeer );
                    if ( $this->SSLCAInfo )
                    {
                        curl_setopt( $ch, CURLOPT_CAINFO, $this->SSLCAInfo );
                    }
                }

                $rawResponse = curl_exec( $ch );

                if ( $rawResponse === false )
                {
                    // grepping through curl sources, we find out the curl error range
                    // to be 1 to 82 (as of curl 7.19.4)
                    $this->errorNumber = curl_errno( $ch ) * -1;
                    $this->errorString = curl_error( $ch );
                    curl_close( $ch );
                    return 0;
                }

                curl_close( $ch );
            }
            else
            {
                $this->errorNumber = CURLE_FAILED_INIT * -1;
                $this->errorString = "Error: could not send the request. Could not initialize CURL.";
                return 0;
            }
        }

        $respArray = $this->parseHTTPResponse( $rawResponse, $useCURL );
        // in case an HTTP error is encountered, error number and string will have been set
        if ( $respArray === false )
        {
            return 0;
        }

        $response = new eZRESTResponse( $request->name() );
        $response->decodeStream( $request, $rawResponse, $respArray['headers'], $respArray['cookies'], $respArray['status_code'] );
        return $response;
    }

    /**
     * Build and return full HTTP payload out of a request obj (and other server status vars)
     * @param eZRESTRequest $request
     * @return string
     * @todo the API and code of this function is quite ugly ($forCurl usage)
     */
    protected function payload( $request, $forCurl = false )
    {
        $RequestHeaders = array();

        // backward compatibility: if request does not specify a verb, let the client
        // pick one on its own
        $verb = $request->method();
        if ( $verb == '' )
        {
            $verb = $this->Verb;
        }

        if ( !$forCurl )
        {
            // if no proxy in use, URLS in request are not absolute but relative
            $uri = $request->requestURI( $this->Path );

            // omit port if standard one is used
            if ( ( $this->Protocol == 'http' && $this->Port == 80 ) || ( $this->Protocol == 'https' && $this->Port == 8443 ) )
            {
                $port = '';
            }
            else
            {
                $port = ':' . $this->Port;
            }

            if ( $this->Proxy != '' )
            {

                // if proxy in use, URLS in request are absolute
                $uri = $this->Protocol . '://' . $this->Server . $port . $uri;
                if ( $this->ProxyLogin != '' )
                {
                    if ( $this->ProxyAuthType != 1 )
                    {
                        //error_log( 'Only Basic auth to proxy is supported yet' );
                    }
                    $RequestHeaders = array( 'Proxy-Authorization' => 'Basic ' . base64_encode( $this->ProxyLogin . ':' . $this->ProxyPassword ) );
                }
            }

            $HTTPRequest = $verb . " " . $uri . " HTTP/1.0\r\nHost: " . $this->Server . $port . "\r\nConnection: close\r\n";
        }

        // added extra request headers for eg SOAP clients
        /// @bug what if both client and request want to add eg COOKIES?
        $RequestHeaders = array_merge( $this->RequestHeaders, $request->RequestHeaders(), $RequestHeaders );

        if ( !$forCurl )
        {
            if ( $this->login() != "" )
            {
                if ( $this->AuthType != 1 )
                {
                    //error_log( 'Only Basic auth is supported' );
                }
                $RequestHeaders['Authorization'] = "Basic " . base64_encode( $this->login() . ":" . $this->password() );
            }

            if ( $this->UserAgent != '' )
            {
                $RequestHeaders['User-Agent'] = $this->UserAgent;
            }
        }
        if ( $this->AcceptedCompression != '' )
        {
            $RequestHeaders['Accept-Encoding'] = $this->AcceptedCompression;
        }

        $payload = $request->payload();
        if ( $payload !== '' )
        {
            if ( ( $this->RequestCompression == 'gzip' || $this->RequestCompression == 'deflate' ) && function_exists( 'gzdeflate' ) )
            {
                if ( $this->RequestCompression == 'gzip' )
                {
                    $a = @gzencode( $payload );
                    if ( $a )
                    {
                        $payload = $a;
                        $RequestHeaders['Content-Encoding'] = 'gzip';
                    }
                }
                else
                {
                    $a = @gzcompress( $payload );
                    if ( $a )
                    {
                        $payload = $a;
                        $RequestHeaders['Content-Encoding'] = 'deflate';
                    }
                }
            }

            $RequestHeaders['Content-Type'] = "application/json";
            if ( !$forCurl )
            {
                $RequestHeaders['Content-Length'] = strlen( $payload );
            }
        }

        foreach ( $RequestHeaders as $key => $val )
        {
            $RequestHeaders[$key] = "$key: $val";
        }

        if ( !$forCurl )
        {
            /// @bug in case there is no $RequestHeaders, we send one crlf too much?
            return $HTTPRequest . implode( "\r\n", $RequestHeaders ) . "\r\n\r\n" . $payload;
        }

        return array( $verb, $RequestHeaders, $payload );
    }

    /**
     * HTTP parsing code taken from the phpxmlrpc lib - should be battle worn.
     * @todo look at PEAR, ZEND, other libs, if they do it better...
     * @todo when gettig 204, 205 responses, we should not return body
     */
    protected function parseHTTPResponse( &$data, $headersProcessed = false )
    {
        if ( $data == '' )
        {
            return array( self::ERROR_NO_DATA, 'No data received from server.' );
        }

        // Support "web-proxy-tunelling" connections for https through proxies
        if ( preg_match( '/^HTTP\/1\.[0-1] 200 Connection established/', $data ) )
        {
            // Look for CR/LF or simple LF as line separator,
            // (even though it is not valid http)
            $pos = strpos( $data,"\r\n\r\n" );
            if ( $pos !== false )
            {
                $bd = $pos + 4;
            }
            else
            {
                $pos = strpos( $data, "\n\n" );
                if ( $pos !== false )
                {
                    $bd = $pos + 2;
                }
                else
                {
                    // No separation between response headers and body: fault?
                    $this->errorNumber = self::ERROR_BAD_RESPONSE;
                    $this->errorString = "HTTPS via proxy error, tunnel connection possibly failed.";
                    return false;
                }
            }
            // this filters out all http headers from proxy.
            // maybe we could take them into account, too?
            $data = substr( $data, $bd );
        }

        // Strip HTTP 1.1 100 Continue header if present
        /// @todo we should only match 100 here - what about eg. 101?
        while ( preg_match( '/^HTTP\/1\.1 1[0-9]{2} /', $data ) )
        {
            $pos = strpos( $data, 'HTTP', 12 );
            if ( $pos === false )
            {
                // server sent a Continue header without any (valid) content following...
                // give the client a chance to know it
                break;
            }
            $data = substr( $data, $pos );
        }

        /// @todo settle on a definitive (or dynamic) list of http return codes accepted
        ///       the spec says btw: applications MUST understand the class of any status code,
        ///       as indicated by the first digit, and treat any unrecognized response as being
        ///       equivalent to the x00 status code of that class, with the exception that an
        ///       unrecognized response MUST NOT be cached
        if ( !preg_match( '/^HTTP\/[0-9]+\.[0-9]+ (20[0-9]) /', $data, $matches ) )
        {
            $errstr = substr( $data, 0, strpos( $data, "\n" ) - 1 );
            if ( preg_match( '/^HTTP\/[0-9]+\.[0-9]+ ([0-9]{3}) (.*)/', $errstr, $matches ) )
            {
                $this->errorNumber = self::ERROR_NON_200_RESPONSE;
                $this->errorString = 'HTTP error ' . $matches[1] . ' (' . $matches[2] . ')';
            }
            else
            {
                $this->errorNumber = self::ERROR_BAD_RESPONSE;
                $this->errorString = 'HTTP error (' . $errstr . ')';
            }
            return false;
        }
        $statusCode = $matches[1];

        $headers = array();
        $cookies = array();

        // be tolerant to usage of \n instead of \r\n to separate headers and data
        // (even though it is not valid http)
        $pos = strpos( $data, "\r\n\r\n" );
        if ( $pos !== false )
        {
            $bd = $pos + 4;
        }
        else
        {
            $pos = strpos( $data, "\n\n" );
            if ( $pos !== false )
            {
                $bd = $pos + 2;
            }
            else
            {
                // No separation between response headers and body: fault?
                // we could take some action here instead of going on...
                $bd = 0;
            }
        }
        // headers that can be present many times
        /// @todo doublecheck: is any of these only for requests?
        $mvh = array( 'accept-ranges', 'allow', 'cache-control', 'content-Language', 'pragma', 'proxy-authenticate', 'trailer', 'transfer-encoding', 'vary', 'via', 'warning', 'www-authenticate', );
        // be tolerant to line endings, and extra empty lines
        foreach ( preg_split( "/\r?\n/", trim( substr( $data, 0, $pos ) ) ) as $line )
        {
            // we take care of multi-line headers, multi-valued headers and cookies
            $arr = explode( ':', $line, 2 );
            if ( count( $arr ) > 1 )
            {
                $headerName = strtolower( trim( $arr[0] ) );
                /// @see http://en.wikipedia.org/wiki/HTTP_cookie
                /// @see http://datatracker.ietf.org/wg/httpstate/
                if ( $headerName == 'set-cookie' )
                {
                    // parse cookie attributes, in case user wants to correctly honour them
                    // feature creep: only allow rfc-compliant cookie attributes?
                    // @todo support for server sending multiple time cookie with same name, but using different PATHs?
                    $cookieElements = explode( ';', $arr[1] );
                    $cookie = explode( '=', array_shift( $cookieElements ), 2 );
                    if ( $cookie[0] != '' && count( $cookie ) > 1 )
                    {
                        $cookieName = trim( $cookie[0] );
                        $cookies[$cookieName] = array( 'value' => urldecode( trim( $cookie[1] ) ) );
                        foreach ( $cookieElements as $val )
                        {
                            $vals = explode( '=', $val, 2 );
                            $cookies[$cookieName][trim( $vals[0] )] = trim( @$vals[1] );
                        }
                    }
                }
                else
                {
                    // Some headers (the ones that allow a CSV list of values)
                    // do allow many values to be passed using multiple header lines.
                    // We add content to $headers[$headerName]
                    // instead of replacing it for those...
                    if ( isset( $headers[$headerName] ) && in_array( $headerName, $mvh ) )
                    {
                        $headers[$headerName] .= ', ' . trim( $arr[1] );
                    }
                    else
                    {
                        $headers[$headerName] = trim( $arr[1] );
                    }
                }
            }
            else if ( isset( $headerName ) )
            {
                /// @todo we should test that 1st char of line is either a space or tab
                ///       ('folding of header lines' in rfc 1945)
                ///       and possibly collapse leading whitspace
                $headers[$headerName] .= ' ' . trim( $line );
            }
        }

        $data = substr( $data, $bd );

        // if CURL was used for the call, http headers have been processed,
        // and dechunking + reinflating have been carried out
        if ( !$headersProcessed )
        {
            // Decode chunked encoding sent by http 1.1 servers
            if ( isset( $headers['transfer-encoding'] ) && $headers['transfer-encoding'] == 'chunked' )
            {
                if ( !$data = self::decodeChunked( $data ) )
                {
                    $this->errorNumber = self::ERROR_BAD_RESPONSE;
                    $this->errorString = "Errors occurred when trying to rebuild the chunked data received from server.";
                    return false;
                }
            }

            // Decode gzip-compressed stuff
            // code shamelessly inspired from nusoap library by Dietrich Ayala
            if ( isset( $headers['content-encoding'] ) )
            {
                $headers['content-encoding'] = str_replace( 'x-', '', $headers['content-encoding'] );
                if ( $headers['content-encoding'] == 'deflate' || $headers['content-encoding'] == 'gzip' )
                {
                    // if decoding works, use it. else assume data wasn't gzencoded
                    if ( function_exists( 'gzinflate' ) )
                    {
                        if ( $headers['content-encoding'] == 'deflate' && $degzdata = @gzuncompress( $data ) )
                        {
                            $data = $degzdata;
                        }
                        else if ( $headers['content-encoding'] == 'gzip' && $degzdata = @gzinflate( substr( $data, 10 ) ) )
                        {
                            $data = $degzdata;
                        }
                        else
                        {
                            $this->errorNumber = self::ERROR_BAD_RESPONSE;
                            $this->errorString = "Errors occurred when trying to decode the deflated data received from server.";
                            return false;
                        }
                    }
                    else
                    {
                        $this->errorNumber = self::ERROR_BAD_RESPONSE;
                        $this->errorString = "The server sent deflated data. This php install must have the Zlib extension compiled in to support this.";
                        return false;
                    }
                }
            }
        } // end of 'if needed, de-chunk, re-inflate response'

        return array( 'headers' => $headers, 'cookies' => $cookies, 'status_code' => $statusCode );
    }

    /**
     * decode a string that is encoded w. "chunked" transfer encoding
     * as defined in rfc2068 par. 19.4.6
     * Code shamelessly stolen from nusoap library by Dietrich Ayala
     *
     * @param string $buffer the string to be decoded
     * @return string
     */
    static protected function decodeChunked( $buffer )
    {
        $length = 0;
        $new = '';

        // read chunk-size, chunk-extension (if any) and crlf
        // get the position of the linebreak
        $chunkend = strpos( $buffer, "\r\n" ) + 2;
        $temp = substr( $buffer, 0, $chunkend );
        $chunkSize = hexdec( trim( $temp ) );
        $chunkstart = $chunkend;
        while ( $chunkSize > 0 )
        {
            $chunkend = strpos( $buffer, "\r\n", $chunkstart + $chunkSize );

            // just in case we got a broken connection
            if ( $chunkend == false )
            {
                $chunk = substr( $buffer,$chunkstart );
                // append chunk-data to entity-body
                $new .= $chunk;
                $length += strlen( $chunk );
                break;
            }

            // read chunk-data and crlf
            $chunk = substr( $buffer, $chunkstart, $chunkend - $chunkstart );
            // append chunk-data to entity-body
            $new .= $chunk;
            // length := length + chunk-size
            $length += strlen( $chunk );
            // read chunk-size and crlf
            $chunkstart = $chunkend + 2;

            $chunkend = strpos( $buffer, "\r\n", $chunkstart ) + 2;
            if ( $chunkend == false )
            {
                break; //just in case we got a broken connection
            }
            $temp = substr( $buffer, $chunkstart, $chunkend - $chunkstart );
            $chunkSize = hexdec( trim( $temp ) );
            $chunkstart = $chunkend;
        }
        return $new;
    }

    /*
       One-stop shop for setting all configuration options
       without haviong to write a haundred method calls
       @todo move all of these values to an array, for commodity
       @todo return true if option exists, false otherwise?
   */
    public function setOption( $option, $value )
    {
        switch ( $option )
        {
            case 'timeout':
                $this->Timeout = (int)$value;
                break;
            case 'login':
                $this->Login = $value;
                break;
            case 'password':
                $this->Password = $value;
                break;
            case 'authType':
                $this->AuthType = (int)$value;
                if ( $value != 1 )
                {
                    $this->ForceCURL = true;
                }
                break;
            case 'requestCompression':
                $this->RequestCompression = $value;
                break;
            case 'method':
                $this->Verb = strtoupper( $value );
                break;
            case 'acceptedCompression':
                $this->AcceptedCompression = $value;
                break;
            case 'proxyHost':
                $this->Proxy = $value;
                break;
            case 'proxyPort':
                $this->ProxyPort = ( (int)$value != 0 ? (int)$value : 8080 );
                break;
            case 'proxyUser':
                $this->ProxyUser = $value;
                break;
            case 'proxyPassword':
                $this->ProxyPassword = $value;
                break;
            case 'proxyAuthType':
                $this->ProxyAuthType = (int)$value;
                if ( $value != 1 )
                {
                    $this->ForceCURL = true;
                }
                break;
            case 'forceCURL':
                $this->ForceCURL = (bool)$value;
                break;
            case 'SSLVerifyHost':
                $this->SSLVerifyHost = (int)$value;
                break;
            case 'SSLVerifyPeer':
                $this->SSLVerifyPeer = (bool)$value;
                break;
            case 'SSLCAInfo':
                $this->SSLCAInfo = $value;
                break;
        }
    }

    /**
     * Set many options in one fell swoop
     *
     * @param array $optionArray
     */
    public function setOptions( $optionArray )
    {
        foreach ( $optionArray as $name => $value )
        {
            $this->setOption( $name, $value );
        }
    }

    /**
     * Returns the login, used for HTTP authentification
     */
    public function login()
    {
        return $this->Login;
    }

    /**
     * Returns the password, used for HTTP authentification
     */
    public function password()
    {
        return $this->Password;
    }

    public function errorString()
    {
        return $this->errorString;
    }

    public function errorNumber()
    {
        return $this->errorNumber;
    }

    /**
     * Sends a REST Request to the provider,
     * returning results in a correct format to be used in tpl fetch functions.
     * Optionally it returns the response object instead of the response value.
     *
     * @param string $server provider name from the wsproviders.ini located in the extension's settings
     * @param string $method the webservice method to be executed
     * @param array $parameters parameters for the webservice method
     * @param array $options extra options to be set into the ws client
     * @return array ( 'result' => null ) in case response is a fault one and $returnResponseObject == false
     *
     * @bug returning NULL for non-error responses is fine only as long as the
     *      protocol does not permit empty responses
     */
    static public function send( $server, $method, $parameters, $options = array() )
    {
        // Gets provider's data from the conf
        $ini = eZINI::instance( 'wsproviders.ini' );

        /// check: if section $server does not exist, error out here
        if ( !$ini->hasGroup( $server ) )
        {
            return array( 'error' => 'Trying to call service on undefined server: ' . $server );
        }

        $providerURI = $ini->variable( $server, 'providerUri' );
        if ( $providerURI != '' )
        {
            $url = parse_url( $providerURI );
            if ( !isset( $url['scheme'] ) || !isset( $url['host'] ) )
            {
                return array( 'error' => "Error in user request: bad server url $providerURI for server $server" );
            }
            if ( !isset( $url['path'] ) )
            {
                $url['path'] = '/';
            }
            if ( !isset( $url['port'] ) )
            {
                if ( $url['scheme'] == 'https' )
                {
                    $url['port'] = 443;
                }
                else
                {
                    $url['port'] = 80;
                }
            }
        }

        $client = new self( $url['host'], $url['path'], $url['port'], $url['scheme'] );

        /// other settings
        // 'new style' server config: make it easier to define any desired client setting,
        // even ones added in future releases, without having to parse it by hand in this class
        $providerOptions = $ini->hasVariable( $server, 'Options' ) ? $ini->variable( $server, 'Options' ) : array();

        // add the user-set options on top of the options set in ini file
        $providerOptions = $options + $providerOptions;
        if ( is_array( $providerOptions ) )
        {
            $client->setOptions( $providerOptions );
        }

        $response = $client->sendRequest( new eZRESTRequest( $method, $parameters ) );

        if ( !is_object( $response ) )
        {
            $response = new eZRESTResponse( $method );
            $response->setValue( new eZRESTFault( $client->errorNumber(), $client->errorString() ) );
        }

        return array( 'result' => $response );
    }
}
