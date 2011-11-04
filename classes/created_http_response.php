<?php

class contentStagingCreatedHttpResponse extends ezpRestHttpResponse
{
    public $uris;

    public function __construct( array $uris )
    {
        $this->code = 201;
        $this->message = null;
        $this->uris = $uris;
    }

    public function process( ezcMvcResponseWriter $writer )
    {
        if ( $writer instanceof ezcMvcHttpResponseWriter )
        {
            $writer->headers["HTTP/1.1 " . $this->code] = $this->message;
        }

        $writer->headers['Content-Type'] = 'application/json; charset=UTF-8';
        $writer->headers['Location'] = current( $this->uris );
        $writer->response->body = json_encode( $this->uris );
    }


}

?>
