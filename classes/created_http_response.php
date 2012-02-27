<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

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

        $writer->headers['Location'] = $this->uri;

        // the following two lines do not belong in a status object really,
        // but ezcMvcConfigurableDispatcher::run makes it extremely hard to
        // return a result object with data to be serialized and a non-200 status
        // code...
        $writer->headers['Content-Type'] = 'application/json; charset=UTF-8';
        $writer->response->body = json_encode( array( 'Location' => $this->uri ) );
    }


}
