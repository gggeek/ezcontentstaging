<?php
/**
 * Implements eZ Content Staging tests for the eZSNMPD extension
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2012-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @todo add more info:
 *       . max age of an executing event. Nb: should track execution start, not event creation
 *       . number of events by type ?
 *       . number of pending events per feed (using tabular format)
 */

class eZsnmpdeZContentStagingHandler extends eZsnmpdFlexibleHandler
{
    static $simplequeries = array(
        '1.1' => 'SELECT COUNT(id) AS count FROM ezcontentstaging_event WHERE status = 0',
        '1.2' => 'SELECT COUNT(id) AS count FROM ezcontentstaging_event WHERE status = 1',
        '1.3' => 'SELECT COUNT(id) AS count FROM ezcontentstaging_event WHERE status = 2',
        '1.4' => 'SELECT COUNT(id) AS count FROM ezcontentstaging_event WHERE status = 4',
        '1.5' => 'SELECT MIN(modified) AS count FROM ezcontentstaging_event WHERE status = 0',
    );

    function get( $oid )
    {
        $oidroot = $this->oidRoot();
        $oidroot = $oidroot[0];
        $internaloid = preg_replace( '/\.0$/', '', $oid );
        $internaloid = preg_replace( '/^' . preg_quote( $oidroot ) . '/', '', $internaloid );

        if ( array_key_exists( $internaloid, self::$simplequeries ) )
        {
            $count = -1;
            $db = self::eZDBinstance();
            if ( $db )
            {
                $results = $db->arrayQuery( self::$simplequeries[$internaloid] );
                $db->close();
                if ( is_array( $results ) && count( $results ) )
                {
                    $count = $results[0]['count'];
                }

                if ( $internaloid == '1.5' )
                {
                    $count = ( $count === null ? 0 : time() - $count );
                }
            }
            return array(
                'oid' => $oid,
                'type' => eZSNMPd::TYPE_INTEGER, // counter cannot be used, as it is monotonically increasing
                'value' => $count );
        }

        return self::NO_SUCH_OID;
     }

    function getMIBTree()
    {
        $oidroot = $this->oidRoot();
        $oidroot = rtrim( $oidroot[0], '.' );
        return array(
            'name' => 'eZPublish',
            'children' => array(
                $oidroot => array(
                    'name' => 'eZContentstaging',
                    'children' => array(
                        1 => array(
                            'name' => 'events',
                            'children' => array(
                                1 => array(
                                    'name' => 'ezcontenstagingTosyncEvents',
                                    'syntax' => 'INTEGER',
                                    'description' => 'Number of Sync events in TO_SYNC status'
                                ),
                                2 => array(
                                    'name' => 'ezcontenstagingSyncingEvents',
                                    'syntax' => 'INTEGER',
                                    'description' => 'Number of Sync events in SYNCING status'
                                ),
                                3 => array(
                                    'name' => 'ezcontenstagingSuspendedEvents',
                                    'syntax' => 'INTEGER',
                                    'description' => 'Number of Sync events in SUSPENDED status'
                                ),
                                4 => array(
                                    'name' => 'ezcontenstagingScheduledEvents',
                                    'syntax' => 'INTEGER',
                                    'description' => 'Number of Sync events in SCHEDULED status'
                                ),
                                5 => array(
                                      'name' => 'ezcontenstagingTosyncEventsAge',
                                      'syntax' => 'INTEGER',
                                      'description' => 'Maximum age (in seconds) of events in TO_SYNC status'
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Make sure we use a separate DB connection from the standard one.
     * This allows us to:
     * - catch the exception raised if db is down and keep the script going
     * - run the script in daemon mode without keeping a connection open (as we can close as soon as it is not needed anymore)
     *
     * NB: this function definition is redundant if using ezsnmpd 0.6 or later
     */
    protected static function eZDBinstance()
    {
        try
        {
            $db = eZDB::instance( false, false, true );
            // eZP 4.0 will not raise an exception on connection errors
            if ( !$db->isConnected() )
            {
                return false;
            }
            return $db;
        }
        catch ( Exception $e )
        {
            return false;
        }
    }
}
?>