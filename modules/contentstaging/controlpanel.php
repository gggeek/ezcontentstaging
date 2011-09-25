<?php
/**
* @package ezcontentstaging
*
* @version $Id$;
*
* @author
* @copyright
* @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
*
*/

$Module = $Params['Module'];
$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();
$ini = eZINI::instance();
$serviceIni = eZINI::instance( 'contentstaging.ini' );


/*$user = eZUser::currentUser();
if ( !$user->isLoggedIn() )
    return $Module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );*/

$userID = $user->id();

if ( $http->hasPostVariable( 'syncrun' )  )
{
    /// @todo: test if current user has access to contentstaging/sync

    if ( $http->hasPostVariable( 'SyncArray' ) )
    {
		$syncErrorArray = array();
        // we use a single array-value in html form to make js usage non mandatory
        /// @todo test that is an array ?
        foreach ( $http->postVariable( 'SyncArray' ) as $syncVar )
        {
            $syncVar = explode( '_', $syncVar, 2 );
            $syncObjID = $syncVar[0];
            $syncHost = $syncVar[1];
            $item = eZContentStagingItem::fetch( $syncHost, $syncObjID );
            if ( $item instanceof eZContentStagingItem )
            {
                if ( !$item->sync() )
                {
                    /// @todo decide format for this
                    $syncErrorArray[] = "Object $syncObjID to be synced to srv $syncHost: failure...";
                }
            }
            else
            {
                eZDebug::writeError( "Object $syncObjID to be synced to srv $syncHost gone amiss", __METHOD__ );
            }
        }
    }
	$tpl->setVariable( 'syncErrorArray', $syncErrorArray );
}

/// @todo fetch list of items to be displayed here, not purely in template

$Result = array();
$Result['content'] = $tpl->fetch( 'design:contentstaging/controlpanel.tpl' );
$Result['path'] = array( array( 'text' => ezpI18n::tr( 'staging', 'Content synchronization' ),
								'url' => false ) );

?>