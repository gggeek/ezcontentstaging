<?php
/**
* Sample event creation filter: just logs events to debug log
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

class eZContentStagingEventCreationLoggingFilter implements eZContentStagingEventCreationFilter
{
    public function accept( eZContentStagingEvent $event, $nodeIds )
    {
        eZDebug::writeDebug( var_export( $event, true ), 'Content staging event created' );
        return true;
    }
}
