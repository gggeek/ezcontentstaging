{if is_set( ezini( 'RSSSettings', 'DefaultFeedItemClasses', 'site.ini' )[ $content_object.class_identifier ] )}
    {def $create_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'staging', 'function', 'sync' ) )}
    {if $create_sync_access}
        {if $current_node.synchronization_state|not() )}
		    <a href={concat( "/staging/sync/", $current_node.node_id )|ezurl} title="{'Sync content'|i18n( 'design/ezwebin/parts/website_toolbar' )}">
            <img class="ezwt-input-image" width="16px" height="16px" src={"websitetoolbar/sync.gif"|ezimage} alt="{'Synchronize node'|i18n( 'design/ezwebin/parts/website_toolbar' )}" />
			</a>
        {/if}
    {/if}
{/if}