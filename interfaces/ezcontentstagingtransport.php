<?php
/**
* Interface that every staging transport has to implement
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

interface eZContentStagingTransport
{
    function __construct( eZContentStagingTarget $target );

    /// @todo decide format for return value (class constants, strings, ... ? )
    /// todo decide if shall use exceptions upon errors
    function sync( eZContentStagingItem $item );
}

?>
