<?php

class eZContentStagingCreatedHttpResponse extends ezpRestHttpResponse
{
    public $uris;

    public function __construct( $uri )
    {
        $this->code = 201;
        $this->message = null;
        $this->uri = $uri;
    }

    public function process( ezcMvcResponseWriter $writer )
    {
        if ( $writer instanceof ezcMvcHttpResponseWriter )
        {
            $writer->headers["HTTP/1.1 " . $this->code] = $this->message;
        }

        $writer->headers['Content-Type'] = 'application/json; charset=UTF-8';
        $writer->headers['Location'] = $this->uri;
        $writer->response->body = json_encode( array( 'Location' => $this->uri ) );
    }


}

?>
