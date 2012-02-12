<?php
/**
 *
 *
 * @version $Id$
 * @copyright 2012
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
        return 'v' . $this->version . '/' . $this->route->getPattern();
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
}

?>