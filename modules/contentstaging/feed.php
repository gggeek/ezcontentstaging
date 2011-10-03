<?php
/**
* View used to display one feed (or all of them together), and sync it
* Supports pagination
*
* @todo add functionality to sync complete feed (all items), not just X items
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

//$Module = $Params['Module'];
$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();
$target_id = $Params['target_id'];

//$ini = eZINI::instance();
//$serviceIni = eZINI::instance( 'contentstaging.ini' );

/*$user = eZUser::currentUser();
if ( !$user->isLoggedIn() )
    return $Module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
$userID = $user->id();*/

///
if ( $http->hasPostVariable( 'syncAction' ) )
{
    /// @todo: test if current user has access to contentstaging/sync, as access
    ///        to the view is only limited by 'view'

    $syncErrors = array();
    $syncResults = array();
    if ( $http->hasPostVariable( 'SyncArray' ) && is_array( $http->postVariable( 'SyncArray' ) ) )
    {
        // we use a single array-value in html form to make js usage non mandatory
        foreach ( $http->postVariable( 'SyncArray' ) as $syncVar )
        {
            $syncVar = explode( '_', $syncVar, 2 );
            $syncObjID = $syncVar[0];
            $syncTarget = $syncVar[1];
            $item = eZContentStagingItem::fetch( $syncTarget, $syncObjID );
            /// @todo with finer grained perms, we should check user can sync these items, one by one
            if ( $item instanceof eZContentStagingItem )
            {
                if ( $item->syncItem() !== 0 )
                {
                    $syncErrors[] = "Object $syncObjID to be synchronised to feed $syncHost: failure...";
                }
                else
                {
                    $syncResults[] = "Object $syncObjID successfully synchronised to feed $syncHost";
                }
            }
            else
            {
                eZDebug::writeError( "Object $syncObjID to be syncronised to srv $syncHost gone amiss", __METHOD__ );
            }
        }

    }
    else
    {
        eZDebug::writeError( "No list of objects to be syncronised received. Pen testing? tsk tsk tsk", __METHOD__ );
        $syncErrors[] = "No object to sync...";
    }
    /// @todo decide format for these 2 variables: let translation happen here or in tpl?
	$tpl->setVariable( 'syncErrors', $syncErrors );
    $tpl->setVariable( 'syncResults', $syncResults );

} // end of 'doing sync' action

if ( $target_id !== null )
{
    /// @todo check that target exists (either here or in tpl code
}

/// @todo !important fetch list of items to be displayed here, not purely in template

$tpl->setVariable( 'target_id', $target_id );
$tpl->setVariable( 'view_parameters', array( 'offset', (int)$Params['Offset'] ) );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:contentstaging/feed.tpl' );
$Result['path'] = array( array( 'text' => ezpI18n::tr( 'staging', 'Content synchronization' ),
								'url' => 'contentstaging/feeds' ) );
if ( $target_id == null )
{
    $Result['path'][] = array( 'text' => ezpI18n::tr( 'staging', 'All feeds' ),
                               'url' => false );
}
else
{
    /// @todo use the name of the feed, not its id
    $Result['path'][] = array( 'text' => ezpI18n::tr( 'staging', "Feed: $target_id" ),
                               'url' => false );
}

?>