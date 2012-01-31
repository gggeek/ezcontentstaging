<?php
/**
 * Interface that every event creation filter has to implement
 *
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

interface eZContentStagingEventCreationFilter
{

    /**
     * Hook called by the kernel to check for acceptance of a staging event into the queue
     *
     * @param eZContentStagingEvent $event
     * @param array $nodeIds
     *
     * @return bool true if the event can be queued false if it shouldn't
     */
    public function accept( eZContentStagingEvent $event, $nodeIds );
}
