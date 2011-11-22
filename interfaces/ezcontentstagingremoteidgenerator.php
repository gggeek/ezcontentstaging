<?php
/**
* Interface for classes implementing generation of remote ids
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

interface eZContentStagingRemoteIdGenerator
{
    function __construct( $target );

    /**
    * @return string
    */
    function buildRemoteId( $sourceId, $sourceRemoteId, $type='node' );
}

?>
