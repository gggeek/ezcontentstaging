<?php
/**
 * File containing the ezpRestHttpRequestParser class
 *
 * @copyright Copyright (C) 1999-2011 eZ Systems AS. All rights reserved.
 * @license http://ez.no/software/proprietary_license_options/ez_proprietary_use_license_v1_0 eZ Proprietary Use License v1.0
 *
 */

/**
 * Custom request parser which creates instances of ezpRestRequest.
 *
 * The main difference is that GET and POST data is protected from potential
 * cookie pollution. And each category of variable has its own silo, to prevent
 * one from overwriting another.
 */
class ezpRestHttpRequestParser extends ezcMvcHttpRequestParser
{
    /**
     * @var ezpRestRequest
     */
    protected $request;

    protected function createRequestObject()
    {
        return new ezpRestRequest();
    }

    protected function processVariables()
    {
        $this->request->variables = $this->fillVariables();
        $this->request->contentVariables = $this->fillContentVariables();
        $this->request->get = $_GET;
        $this->request->post = $_POST;
    }

    protected function processStandardHeaders()
    {
        $this->processEncryption();
        parent::processStandardHeaders( );
    }

    /**
     * Sets the isEncrypted flag if HTTPS is on.
     *
     * @return void
     */
    protected function processEncryption()
    {
        if ( !empty( $_SERVER['HTTPS'] ) )
            $this->request->isEncrypted = true;
    }

    /**
     * Extract variables to be used internally from GET
     * @return array
     */
    protected function fillVariables()
    {
        $variables = array();
        $internalVariables = array( 'ResponseGroups' ); // Expected variables

        foreach( $internalVariables as $internalVariable )
        {
            if( isset( $_GET[$internalVariable] ) )
            {
                // Extract and organize variables as expected
                switch( $internalVariable )
                {
                    case 'ResponseGroups':
                        $variables[$internalVariable] = explode( ',', $_GET[$internalVariable] );
                        break;

                    default:
                        $variables[$internalVariable] = $_GET[$internalVariable];
                }

                unset( $_GET[$internalVariable] );
            }
            else
            {
                switch( $internalVariable )
                {
                    case 'ResponseGroups':
                        $variables[$internalVariable] = array();
                        break;

                    default:
                        $variables[$internalVariable] = null;
                }
            }
        }

        return $variables;
    }

    /**
     *  Overloads processBody() to add support for body on POST and PUT
     */
    protected function processBody()
    {
        $req = $this->request;

        if ( $req->protocol === 'http-put' ||  $req->protocol === 'http-post' )
        {
            $req->body = file_get_contents( "php://input" );

            if ( isset( $headers['CONTENT_TYPE'] ) &&  strlen( $req->body ) > 0 )
            {
                switch( $headers['CONTENT_TYPE'] )
                {
                    case 'application/json':
                    case 'json':
                        $variables = json_decode( $this->request->body, true );
                        if ( is_array( $variables ) )
                        {
                            $this->request->inputVariables = $variables;
                        }
                        else
                        {
                            /// @todo log warning
                        }
                        break;
                    case 'application/x-www-form-urlencoded':
                        if ( $req->protocol === 'http-put' )
                        {
                            $variables = array();
                            parse_str( $this->request->body, $variables );
                            $this->request->inputVariables = $variables;
                        }
                        else
                        {
                            $this->request->inputVariables = $_POST;
                        }
                        break;
                    default:
                        /// @todo log warning
                }
            }
        }
    }

    /**
     * Extract variables related to content from GET
     * @return array
     */
    protected function fillContentVariables()
    {
        $contentVariables = array();
        $expectedVariables = array( 'Translation', 'OutputFormat' );

        foreach( $expectedVariables as $variable )
        {
            if( isset( $_GET[$variable] ) )
            {
                // Extract and organize variables as expected
                switch( $variable )
                {
                    case 'Translation': // @TODO => Make some control on the locale provided
                    default:
                        $contentVariables[$variable] = $_GET[$variable];
                }

                unset( $_GET[$variable] );
            }
            else
            {
                $contentVariables[$variable] = null;
            }
        }

        return $contentVariables;
    }
}
