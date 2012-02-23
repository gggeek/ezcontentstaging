<?php
/**
* Demo filter: cleans away ALL attributes
*
* @package ezcontentstaging
*
* @author
* @copyright
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

?>
