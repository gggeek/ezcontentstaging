<?php
/**
 * Base class for common methods for staging transport subclasses
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZBaseStagingTransport
{
    const DIFF_UNKNOWNTRANSPORT = 2048;
    const DIFF_TRANSPORTERROR = 1;

    const DIFF_NODE_MISSING = 2;
    const DIFF_NODE_PARENT = 4;
    const DIFF_NODE_VISIBILITY = 8;
    const DIFF_NODE_SORTFIELD = 16;
    const DIFF_NODE_SORTORDER = 32;

    const DIFF_OBJECT_MISSING = 64;
    const DIFF_OBJECT_SECTION = 128;
    const DIFF_OBJECT_STATE = 256;
    const DIFF_OBJECT_ALWAYSAVAILABLE = 1024;

    // should be a constant really, but php allows no arrays as class consts
    static $diffDescriptions = array(
        self::DIFF_TRANSPORTERROR => 'Error while checking differences',
        self::DIFF_NODE_MISSING => 'Node is missing',
        self::DIFF_NODE_PARENT => 'Parent node is different',
        self::DIFF_NODE_VISIBILITY => 'Node visibility is different',
        self::DIFF_NODE_SORTFIELD => 'Node sorting is different',
        self::DIFF_NODE_SORTORDER => 'Node sorting order is different',
        self::DIFF_OBJECT_MISSING => 'Object is missing',
        self::DIFF_OBJECT_SECTION => 'Oject section is different',
        self::DIFF_OBJECT_STATE => 'Object states are different',
        self::DIFF_OBJECT_ALWAYSAVAILABLE => 'Object default availability is different',
    );

    /**
     * Decodes the bitmask of possible differences to an aray (easier to manipulate in templates)
     * @return array code => description
     */
    static public function diffmask2array( $bitmask )
    {
        $diffs = array();
        foreach ( self::$diffDescriptions as $code => $desc )
        {
            if ( $code & $bitmask )
            {
                $diffs[$code] = $desc;
            }
        }
        return $diffs;
    }
}
