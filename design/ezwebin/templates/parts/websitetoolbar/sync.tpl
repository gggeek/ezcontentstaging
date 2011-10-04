{**
  Website toolbar button dedicated to syncing an object via contentstaging extension

  @todo: there can be many sinc targets for a given node, here we only allow user
         to sync all of them at once. Should become a drop-down list in the future
*}
{* gg: what is this rss thing doing here? *}
{*if is_set( ezini( 'RSSSettings', 'DefaultFeedItemClasses', 'site.ini' )[ $content_object.class_identifier ] )*}

    {* @todo check 2 things: if user can sync and if he can see need-to-sync state *}
    {def $view_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'view' ) )}
    {if $view_sync_access}
        {def $create_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'sync' ) )
             $needs_sync = fetch( 'contentstaging', 'objectsynctargets', hash( 'object_id', $current_node.contentobject_id ) )}
        {* @todo if user can sync only to some targets, filter them here *}
        {if $needs_sync|count()}
            {* @todo add ajax support via ezjscore and/or REST *}
		    {if $create_sync_access}<a href={concat( "/contentstaging/sync/", $current_node.contentobject_id )|ezurl} title="{'Sync content'|i18n( 'design/ezwebin/parts/website_toolbar' )}">{/if}
                <img class="ezwt-input-image" width="16px" height="16px" src={"websitetoolbar/sync.gif"|ezimage} alt="{'Synchronize node'|i18n( 'design/ezwebin/parts/website_toolbar' )}" />
			{if $create_sync_access}</a>{/if}
		{else}
		    {* @todo show an icon: no need to sync ? *}
        {/if}
        {undef $create_sync_access $needs_sync}
    {/if}
    {undef $view_sync_access}

{*/if*}