<?php
/**
 * View used to display list of feeds
 *
 * @todo add functionality to add, remove feeds
 *
 * @package ezcontentstaging
 *
 * @author
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 */

$module = $Params['Module'];
$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();

if ( $module->isCurrentAction( 'ResetFeeds' ) )
{
    // test if current user has access to contentstaging/manage, as access to this view is only limited by 'view'
    $user = eZUser::currentUser();
    $hasAccess = $user->hasAccessTo( 'contentstaging', 'manage' );
    if ( $hasAccess['accessWord'] === 'no' )
    {
        return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
    }

    $actionErrors = array();
    $actionResults = array();
    if ( $http->hasPostVariable( 'feeds' ) && is_array( $http->postVariable( 'feeds' ) ) )
    {
        $toreset = array();
        foreach ( $http->postVariable( 'feeds' ) as $feedId )
        {
            $feed = eZContentStagingTarget::fetch( $feedId );
            /// @todo with finer grained perms, we should check user can sync these items, one by one
            if ( $feed instanceof eZContentStagingTarget )
            {
                $toreset[] = $feedId;
            }
            else
            {
                eZDebug::writeError( "Invalid feed id received for reset: $feedId", 'contentstaging/feeds' );
            }
        }

        if ( count( $toreset ) )
        {
            /// @todo we are actually faking the number of deleted events...
            $out = eZContentStagingEvent::removeEventsByTargets( $toreset );
            /// @todo apply i18n to messages
            if ( $out === false )
            {
                $actionErrors[] = "Error: feeds not reset (" . implode( ', ', $toreset ) . ')';
            }
            else
            {
                $actionResults[] = "feeds reset (" . implode( ', ', $toreset ) . "): $out events removed";
            }
        }
        else
        {
            /// @todo apply i18n to message
            $actionErrors[] = "No feeds to reset...";
        }
    }
    else
    {
        eZDebug::writeError( "No list of feeds to be reset received. Pen testing? tsk tsk tsk", __METHOD__ );
        /// @todo apply i18n to message
        $actionErrors[] = "No feeds to reset...";
    }

    /// @todo decide format for these 2 variables: let translation happen here or in tpl?
    $tpl->setVariable( 'action_errors', $actionErrors );
    $tpl->setVariable( 'action_results', $actionResults );
    $tpl->setVariable( 'action', 'reinitialization' );
}
elseif ( $module->isCurrentAction( 'InitializeFeeds' ) )
{
    // test if current user has access to contentstaging/manage, as access to this view is only limited by 'view'
    $user = eZUser::currentUser();
    $hasAccess = $user->hasAccessTo( 'contentstaging', 'manage' );
    if ( $hasAccess['accessWord'] === 'no' )
    {
        return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
    }

    $actionErrors = array();
    $actionResults = array();
    if ( $http->hasPostVariable( 'feeds' ) && is_array( $http->postVariable( 'feeds' ) ) )
    {
        $toinitialize = array();
        foreach ( $http->postVariable( 'feeds' ) as $feedId )
        {
            $feed = eZContentStagingTarget::fetch( $feedId );
            /// @todo with finer grained perms, we should check user can sync these items, one by one
            if ( $feed instanceof eZContentStagingTarget )
            {
                $toinitialize[] = $feed;
            }
            else
            {
                eZDebug::writeError( "Invalid feed id received for initialization: $feedId", 'contentstaging/feeds' );
            }
        }

        if ( count( $toinitialize ) )
        {
            foreach( $toinitialize as $feed )
            {
                $errors = array();
                foreach( $feed->initializeRootItems() as $result )
                {
                    if ( $result != 0 )
                    {
                        $errors[] = $result;
                    }
                }
                /// @todo apply i18n to messages
                if ( count( $errors ) )
                {
                    $actionErrors[] = "Error: feed " . $feed->attribute( 'name' ) . 'not initialized ( ' . implode( ', ', $errors ) . ' )';
                }
                else
                {
                    $actionResults[] = "Feed " . $feed->attribute( 'name' ) . " initialized";
                }
            }
        }
        else
        {
            /// @todo apply i18n to message
            $actionErrors[] = "No feeds to initialize...";
        }
    }
    else
    {
        eZDebug::writeError( "No list of feeds to be initialized received. Pen testing? tsk tsk tsk", __METHOD__ );
        /// @todo apply i18n to message
        $actionErrors[] = "No feeds to initialize...";
    }

    /// @todo decide format for these 2 variables: let translation happen here or in tpl?
    $tpl->setVariable( 'action_errors', $actionErrors );
    $tpl->setVariable( 'action_results', $actionResults );
    $tpl->setVariable( 'action', 'initialization' );
}

$tpl->setVariable( 'feeds', eZContentStagingTarget::fetchList() );

$Result['content'] = $tpl->fetch( 'design:contentstaging/feeds.tpl' );
$Result['path'] = array( array( 'text' => ezpI18n::tr( 'ezcontentstaging', 'Content synchronization' ),
                                'url' => false ) );
?>