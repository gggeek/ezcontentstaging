<?php
/**
* Interface for classes implementing filtering of outgoing fields (in object creation/
* update calls).
* This can be used to set up partial replication of content, where eg. some attributes
* in the objects of the source server are considered to be private and not to
* be synchronized to the target server.
*
* @package ezcontentstaging
*
* @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

interface eZContentStagingFieldFilter
{
    function __construct( $target );

    /**
     * @return bool true to allow attribute to go through, false if not
     */
    public function accept( eZContentObjectAttribute $attribute );
}
