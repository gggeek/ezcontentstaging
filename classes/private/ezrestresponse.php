<?php
/**
 * Class used to wrap REST responses.
 * Needs the json ext. for json
 *
 * @author G. Giunta
 * @copyright (C) 2009-2012 G. Giunta
 */

class eZRESTResponse
{
    const INVALIDRESPONSEERROR = -301;

    /// @todo use a single array for all error strings
    const INVALIDRESPONSESTRING = 'Response received from server is not valid';

    /// Contains the response value
    protected $Value = false;
    /// Contains fault string
    protected $FaultString = false;
    /// Contains the fault code
    protected $FaultCode = false;
    /// Contains true if the response was an fault
    protected $IsFault = false;
    /// Contains the name of the response, i.e. function call name
    protected $Name;

    public function __construct( $name = '' )
    {
        $this->Name = $name;
    }

    /**
     * Returns true if the response was a fault
     */
    public function isFault()
    {
        return $this->IsFault;
    }

    /**
     * Returns the fault code
     */
    public function faultCode()
    {
        return $this->FaultCode;
    }

    /**
     * Returns the fault string
     */
    public function faultString()
    {
        return $this->FaultString;
    }

    /**
     * Returns the response value as plain php value
     */
    public function value()
    {
        return $this->Value;
    }

    /**
     * Sets the value of the response (plain php value).
     * If $values is (subclass of) eZRESTFault, sets the response to error state.
     */
    public function setValue( $value )
    {
        $this->Value = $value;
        if ( $value instanceof eZRESTFault )
        {
            $this->IsFault = true;
            $this->FaultCode = $value->faultCode();
            $this->FaultString = $value->faultString();
        }
        else
        {
            $this->IsFault = false;
        }
    }

    /**
     * Decodes the REST response stream.
     * Request is not used, kept for compat with sibling classes
     * Name is not set to response from request - a bit weird...
     */
    public function decodeStream( $request, $stream, $headers = false, $cookies = array(), $statusCode = "200" )
    {
        $this->Cookies = $cookies;

        // Allow empty payloads regardless of declared content type, for 204 and 205 responses
        if ( $statusCode == '204' || $statusCode == '205' )
        {
            if ( $stream == '' && ( !isset( $headers['content-length'] ) || $headers['content-length'] == 0 ) )
            {
                $this->Value = null;
                $this->IsFault = false;
                $this->FaultString = false;
                $this->FaultCode = false;
                return;
            }

            /// @todo this is not valid according to rfc 2616 - but we should leave that control to client really
            $this->IsFault = true;
            $this->FaultCode = self::INVALIDRESPONSEERROR;
            $this->FaultString = self::INVALIDRESPONSESTRING . " (received http response 204/205 with a body. Not valid http)";
        }

        $val = json_decode( $stream, true );
        if ( function_exists( 'json_last_error' ) )
        {
            $err = json_last_error();
        }
        else
        {
            $err = ( $val === null ) ? 1 : false;
        }
        if ( $err )
        {
            $this->IsFault = true;
            $this->FaultCode = self::INVALIDRESPONSEERROR;
            $this->FaultString = self::INVALIDRESPONSESTRING . ' json. Decoding error: ' . $err;
        }
        else
        {
            $this->Value = $val;
        }
    }
}
