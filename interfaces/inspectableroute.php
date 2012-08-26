<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

interface ezpRestInspectableRoute
{
    /// returns NULL for unversioned routes
    public function getVersion();

    /**
     * Returns a "typical URL" used to access this route, ideally to have tools
     * building automated calls to the api.
     * Since the REST api internals are a bit limited, the provider part in the
     * url is omitted here.
     */
    public function getPattern();

    /// the HTTP verb matched
    /// @todo should be an array, when our routes do support it...
    public function getVerb();

    /// returns human-readable docs
    public function getDescription();

    public function getControllerClassName();

    public function getAction();
}
