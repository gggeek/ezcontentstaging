<?php
/**
 * A class used to ping the ezcontentstaging extension rest provider.
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZContentStagingRestProviderPing extends ezpRestMvcController
{
    protected function getProviderClass()
    {
        return 'eZContentStagingRestApiProvider';
    }

    /**
     * Ping action to test communication
     */
    public function doPing()
    {
        $return = new ezpRestMvcResult();
        $return->variables = array( "pong" );
        return $return;
    }
}
