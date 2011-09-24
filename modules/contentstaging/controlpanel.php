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

	if ( $http->hasPostVariable( 'SyncIDArray' ) )
    {
		$syncIDResultArray = array();
        $syncIDArray = $http->postVariable( 'SyncIDArray' );

        foreach ( $syncIDArray as $syncID )
        {
            $oNode = eZContentObjectTreeNode::fetch( $syncID );
            if ( $oNode instanceof eZContentObjectTreeNode )
            {
                //TODO CALL REST
				$restUrl = $serviceIni->variable($StagingSettings,'RestRootUrl')."/<prefix>/<provider>/<version>/<call>/<params>/";
				$syncIDResultArray = $http->sendHTTPRequest ( $restUrl, false, false, $ini->variable('SiteSettings','SiteName') );
            }
            else
            {
                eZDebug::writeError( "", __METHOD__ );
            }
        }
    }
	$tpl->setVariable('syncIDResultArray', $syncIDResultArray );
}

$Result = array();
$Result['content'] = $tpl->fetch( 'design:contentstaging/controlpanel.tpl' );
$Result['path'] = array( array( 'text' => ezpI18n::tr( 'staging', 'Content synchronization' ),
								'url' => false ) );

?>