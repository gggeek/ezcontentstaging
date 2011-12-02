{**
  Website toolbar button dedicated to syncing an object via contentstaging extension

  @todo: there can be many sync targets for a given node, here we only allow user
         to sync all of them at once. Should become a drop-down list in the future
*}

    {* check 2 things: if user can sync and if he can see need-to-sync state *}
    {def $view_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'view' ) )}
    {if $view_sync_access}
        {* @todo check if we can actually be editing a version in a different language than the default one set in site.ini... *}
        {def $current_lang = ezini( 'RegionalSettings', 'ContentObjectLocale' )
             $create_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'sync' ) )
             $needs_sync = fetch( 'contentstaging', 'node_sync_events_by_target', hash( 'node_id', $current_node.node_id, 'language', $current_lang ) )
             $feeds = fetch( 'contentstaging', 'sync_feeds_by_node', hash( 'node_id', $current_node.node_id) ) }
        {if $needs_sync|count()}

            {def $preferred_lib = ezini('eZJSCore', 'PreferredLibrary', 'ezjscore.ini')}
            {if array( 'yui3', 'jquery' )|contains( $preferred_lib )|not()}
                {* Prefer jQuery if something else is used globally, since it's smaller then yui3. *}
                {set $preferred_lib = 'jquery'}
            {/if}
            {ezscript_require( array( concat( 'ezjsc::', $preferred_lib ), concat( 'ezjsc::', $preferred_lib, 'io' ), concat( 'ezcontentstaging_', $preferred_lib, '.js' ) ) )}

            {* @todo if user can sync only to some targets, filter them here *}
            {def $ids=array()}
            {foreach $needs_sync as $target => $events}
                {foreach $events as $event}
                    {set $ids = $ids|merge( $event.id )}
                {/foreach}
            {/foreach}
            {* @todo link to syncnode view should take into account current language *}

            {if $create_sync_access}
            <form method="post" action={"/contentstaging/syncnode/"|ezurl} class="right">
            <div id="ezwt-stagingaction" class="ezwt-actiongroup">
                <input type="hidden" name="NodeID" value="{$current_node.node_id}" />
                <input type="hidden" name="ObjectID" value="{$content_object.id}" />
                <select name="TargetId" id="TargetId">                    
                    {foreach $feeds as $feed_name => $feed}
                        <option value="{$feed_name}">{$feed_name}</option>
                    {/foreach}
                </select>
                <input type="image" class="ezwt-input-image" src={"websitetoolbar/sync.gif"|ezimage} title="{'Synchronize node'|i18n( 'design/ezwebin/parts/website_toolbar')} name="SynchronizeAction" />
            </div>
            </form>
            {/if}

            {undef $ids $preferred_lib}
        {else}
            {* @todo show an icon: no need to sync ? *}
        {/if}
        {undef $create_sync_access $needs_sync $current_lang}
    {/if}
    {undef $view_sync_access}