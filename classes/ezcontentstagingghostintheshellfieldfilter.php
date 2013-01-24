<?php
/**
 * Demo filter: cleans away ALL attributes
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZContentStagingGhostintheshellFieldFilter implements eZContentStagingFieldFilter
{
    function __construct( $target )
    {

    }

    public function accept( eZContentObjectAttribute $attribute )
    {
        return false;
    }
}
