{**
  Website toolbar button dedicated to syncing an object via contentstaging extension

  @todo: there can be many sync targets for a given node, here we only allow user
         to sync one of them at once. Should add sync-all button?
*}

{* check 2 things: if user can sync and if he can see need-to-sync state *}
{def $view_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'view' ) )}
{if $view_sync_access}
    {* @todo check if we can actually be editing a version in a different language than the default one set in site.ini... *}
    {def $needs_sync = fetch( 'contentstaging', 'node_sync_events_by_target', hash( 'node_id', $current_node.node_id,
                                                                                    'language', ezini( 'RegionalSettings', 'ContentObjectLocale' ) ) )
         $create_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'sync' ) )
		 $title = ''}
    {if $needs_sync|count()}
        {def $preferred_lib = ezini('eZJSCore', 'PreferredLibrary', 'ezjscore.ini')}
        {if array( 'yui3', 'jquery' )|contains( $preferred_lib )|not()}
            {* Prefer jQuery if something else is used globally, since it's smaller then yui3. *}
            {set $preferred_lib = 'jquery'}
        {/if}
        {ezscript_require( array( concat( 'ezjsc::', $preferred_lib ), concat( 'ezjsc::', $preferred_lib, 'io' ), concat( 'ezcontentstaging_', $preferred_lib, '.js' ) ) )}
        {undef $preferred_lib}
    {/if}
    <div id="ezwt-stagingaction" class="ezwt-actiongroup">

    {foreach ezini( 'GeneralSettings', 'TargetList', 'contentstaging.ini' ) as $target}
        {* @todo use feed name, not id *}
        {set $title = concat( 'Feed: ', $target, ' - ' )}
        {*$target}
        {$needs_sync.$target|attribute()*}
        {if is_set($needs_sync.$target)}
            {* @todo if user can sync only to some targets, filter them here *}
            {foreach $needs_sync.$target as $i => $event}
                {if $i|gt( 0 )}
                    {set $title = $title|append( ', ' ) }
                {/if}
                {set $title = $title|append( $event.to_sync_string|i18n( 'contentstaging' ) ) }
            {/foreach}
            {if $create_sync_access}
            <form method="post" action={"/contentstaging/syncevents"|ezurl} class="right">
                <input type="submit" class="ezwt-input-image" src={"websitetoolbar/sync.gif"|ezimage} title="{$title|wash()}" name="SynchronizeAction" />
                <input type="hidden" name="NodeID" value="{$current_node.node_id}" />
                <input type="hidden" name="ObjectID" value="{$content_object.id}" />
                <input type="hidden" name="TargetId" value="{$target}">
            </form>
            {else}
               <img src={'sync.gif'|ezimage()} alt="{$title|wash()}" title="{$title|wash()}" />
            {/if}
        {else}
            {set $title = $title|append( 'no need to synchronize node'|i18n( 'contentstaging' ) )}
            <img src={'sync-disabled.gif'|ezimage()} alt="{$title|wash()}" title="{$title|wash()}" />
        {/if}
    {/foreach}
    </div>
    {undef $needs_sync $create_sync_access $title}
{/if}
{undef $view_sync_access}