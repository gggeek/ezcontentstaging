<?php
/**
* @package ezcontentstaging
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/


class ezpRestInspectableRailsRoute extends ezpMvcRailsRoute implements ezpRestInspectableRoute
{

    public function getVersion()
    {
        return null;
    }

    /**
     * Returns a "typical URL" used to access this route.
     * Reversible routes can generate an URL, but we want the parameters to be shown
     * in some understandable form. Hence we call this "pattern" and do not rely
     * on generateUrl().
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    public function getVerb()
    {
        return str_replace( 'http-', '', $this->protocol );
    }

    /// @todo use php introspection + phpdoc parsing
    public function getDescription()
    {
        return "";
    }
}

?>