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
             $needs_sync = fetch( 'contentstaging', 'node_sync_events_by_target', hash( 'node_id', $current_node.node_id ) )}
        {if $needs_sync|count()}
            {* @todo if user can sync only to some targets, filter them here *}
            {def $ids=array()}
            {foreach $needs_sync as $target => $events}
                {foreach $events as $event}
                    {set $ids = $ids|merge( $event.id )}
                {/foreach}
            {/foreach}
            {* @todo add ajax support via ezjscore and/or REST *}
		    {if $create_sync_access}<a href={concat( "/contentstaging/sync/", $ids|implode(',') )|ezurl} title="{'Sync content'|i18n( 'design/ezwebin/parts/website_toolbar' )}">{/if}
                <img class="ezwt-input-image" width="16px" height="16px" src={"websitetoolbar/sync.gif"|ezimage} alt="{'Synchronize node'|i18n( 'design/ezwebin/parts/website_toolbar' )}" />
			{if $create_sync_access}</a>{/if}
			{undef $ids}
		{else}
		    {* @todo show an icon: no need to sync ? *}
        {/if}
        {undef $create_sync_access $needs_sync}
    {/if}
    {undef $view_sync_access}

{*/if*}