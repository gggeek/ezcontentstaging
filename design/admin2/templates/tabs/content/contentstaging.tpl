{* Content staging panel for content view in admin interface *}

{*$node|attribute(show, 1)*}
{def $view_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'view' ) )}
{if $view_sync_access}

    {def $preferred_lib = ezini('eZJSCore', 'PreferredLibrary', 'ezjscore.ini')}
    {if array( 'yui3', 'jquery' )|contains( $preferred_lib )|not()}
        {* Prefer jQuery if something else is used globally, since it's smaller then yui3. *}
        {set $preferred_lib = 'jquery'}
    {/if}
    {ezscript_require( array( concat( 'ezjsc::', $preferred_lib ), concat( 'ezjsc::', $preferred_lib, 'io' ), concat( 'ezcontentstaging_', $preferred_lib, '.js' ) ) )}
    {undef $preferred_lib}

    {def $assignments = $node.object.assigned_nodes}
    {foreach $assignments as $assignment}

        {def $needs_sync = fetch( 'contentstaging', 'node_sync_events_by_target', hash( 'node_id', $assignment.node_id,
                                                                                        'language', ezini( 'RegionalSettings', 'ContentObjectLocale' ) ) )
             $create_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'sync' ) )
    		 $feeds = fetch( 'contentstaging', 'sync_feeds_by_node', hash( 'node_id', $assignment.node_id ) )}

    		{if $assignments|count()|gt(1)}
    		    <h4>Location: {$assignment.path_identification_string}</h4>
    		{/if}

    		<table class="list">
        	<tr><th>{'Feed'|i18n('contentstaging')}</th><th>{'To synchronize'}</th><th>{'Verify status on target server'|i18n('contentstaging')}</th></tr>
            {foreach $feeds as $feedid => $feed}
                <tr><td>{$feed.name|wash()}</td>
                <td>{if is_set($needs_sync.$feedid)}
                    {foreach $needs_sync.$feedid as $i => $event}
                        {$event.to_sync_string|i18n( 'contentstaging' ) )}
                        {delimiter}, {/delimiter}
                    {/foreach}
                {else}
                    -
                {/if}</td>
                <td><a href={concat('contentstaging/checknode/', $assignment.node_id, '/', $feedid)|ezurl()} class="">{'Check'|i18n('contentstaging')}</a></td></tr>
            {/foreach}
            </table>
        {if $feeds|count()|not()}
            {'This location does not have to be synchronized to any feed'|i18n('contentstaging')}
        {/if}
        {undef $needs_sync $create_sync_access $feeds}

    {/foreach}
    {udef $assignments}
{/if}
{undef $view_sync_access}
