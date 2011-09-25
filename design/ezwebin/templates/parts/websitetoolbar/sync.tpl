{* gg: what is this rss thing doing here? *}
{if is_set( ezini( 'RSSSettings', 'DefaultFeedItemClasses', 'site.ini' )[ $content_object.class_identifier ] )}

    {* @todo check 2 things: if user can sync and if he can see need-to-sync state *}
    {def $create_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'sync' ) )}
    {if $create_sync_access}
        {def $needs_sync = fetch( 'contentstaging', 'nodesynctargets', hash( 'node_id', $current_node.node_id ) )}
        {* @todo if user can sync only to some targets, filter them here *}
        {if $needs_sync|count()}
            {* @todo decide format for the sync url: is it called via ajax or is it a plain module view? *}
		    <a href={concat( "/contentstaging/sync/", $current_node.node_id )|ezurl} title="{'Sync content'|i18n( 'design/ezwebin/parts/website_toolbar' )}">
                <img class="ezwt-input-image" width="16px" height="16px" src={"websitetoolbar/sync.gif"|ezimage} alt="{'Synchronize node'|i18n( 'design/ezwebin/parts/website_toolbar' )}" />
			</a>
		{else}
		    {* @todo show an icon: no need to sync *}
        {/if}
    {/if}
    {undef $create_sync_access}

{/if}