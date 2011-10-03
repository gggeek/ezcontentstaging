<?php
/**
*
* View used to sync one item
*
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*/

$syncErrors = array();
$syncResults = array();

/// @todo sanitize $target_id against html injection
$target_id = $Params['target_id'];
$object_id = $Params['object_id'];

$items = array();
if ( $target_id == null )
{
    $items = eZContentStagingItem::fetchByObject( $object_id );
}
else
{
    // check that sync item exists
    $item = eZContentStagingItem::fetch( $target_id, $object_id );
    if ( $item instanceof eZContentStagingItem )
    {
        $items = array( $item );
    }
}

if ( count( $items ) )
{
    foreach( $items as $item )
    {
        /// @todo check that current user can sync - with limitations - this item

        /// go
        if ( ( $result = $item->syncItem() ) !== 0 )
        {
            $syncErrors[] = "Error $result while synchronizing to target " . $item->target_id . "\n";
        }
        else
        {
            $syncResults[] = "";
        }
    }
}
else
{
    // sanitize error msg, just in case
    $syncErrors[] = sprintf( "No item(s) %s/%d to be synchronized", $target_id, $object_id );
}

$tpl = eZTemplate::factory();
$tpl->setVariable( 'syncItems', $items );
$tpl->setVariable( 'syncErrors', $syncErrors );
$tpl->setVariable( 'syncResults', $syncResults );

$Result['content'] = $tpl->fetch( 'design:contentstaging/sync.tpl' );

$Result['path'] = array( array( 'text' => ezpI18n::tr( 'staging', 'Content synchronization' ),
								'url' => 'contentstaging/feeds' ) );
if ( $target_id == null )
{
    $Result['path'][] = array( 'text' => ezpI18n::tr( 'staging', 'All feeds' ),
                               'url' => 'contentstaging/feed' );
}
else
{
    /// @todo use the name of the feed, not its id
    $Result['path'][] = array( 'text' => ezpI18n::tr( 'staging', "Feed: $target_id" ),
                               'url' => "contentstaging/feed/$target_id" );
}
$Result['path'][] = array( 'text' => ezpI18n::tr( 'staging', 'Synchronise object' ),
                           'url' => false );

?>