<?php
/**
 * View used to display list of feeds
 *
 * @todo add functionality to add, remove feeds
 *
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 */
$tpl = eZTemplate::factory();
$tpl->setVariable( 'feeds', eZContentStagingTarget::fetchList() );

$Result['content'] = $tpl->fetch( 'design:contentstaging/feeds.tpl' );
$Result['path'] = array( array( 'text' => ezpI18n::tr( 'staging', 'Content synchronization' ),
                                'url' => false ) );
?>