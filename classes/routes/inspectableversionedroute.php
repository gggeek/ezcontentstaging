<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class ezpRestInspectableVersionedRoute extends ezpRestVersionedRoute implements ezpRestInspectableRoute
{
    public function getVersion()
    {
        return $this->version;
    }

    /// @todo check that base route is inspectable
    public function getPattern()
    {
        return 'v' . $this->version . '/' . ltrim( $this->route->getPattern(), '/' );
    }

    /// @todo check that base route is inspectable
    public function getVerb()
    {
        return $this->route->getVerb();
    }

    /// @todo check that base route is inspectable
    public function getDescription()
    {
        return $this->route->getDescription();
    }

    /// @todo check that base route is inspectable
    public function getControllerClassName()
    {
        return $this->route->getControllerClassName();
    }

    /// @todo check that base route is inspectable
    public function getAction()
    {
        return $this->route->getAction();
    }
}
