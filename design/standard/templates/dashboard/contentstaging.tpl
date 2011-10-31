{**
  Template for the dashboard block listing pending sync events
  (nb: a sync event is neither an object nor a node)
*}

{* gg: cache-block to be reviewed - as content-altering operations might not expire it... *}
{def $user_hash = concat( $user.role_id_list|implode( ',' ), ',', $user.limited_assignment_value_list|implode( ',' ) )}
{cache-block keys=array( $user_hash )}

{* gg: could avoid showing events that are in "syncing" state... *}
{def $sync_events = fetch( 'contentstaging', 'sync_events', hash( 'limit', $block.number_of_items ) )
     $sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'sync' ) )}

<h2>{'Contents synchronization events'|i18n( 'design/admin/dashboard/sync' )}</h2>

{if $sync_events}

<table class="list" cellpadding="0" cellspacing="0" border="0">
    <tr>
        {* gg: should show target_id, sync status, things to sync instead ?
           this whole block is not object-oriented but event oriented anyway *}
        <th>{'Name'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th>{'Type'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th>{'Published'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th>{'Author'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th class="tight"></th>
    </tr>
    {foreach $sync_events as $sync_event sequence array( 'bglight', 'bgdark' ) as $style}
        <tr class="{$style}">
            <td>
                {* for deleted objects we have no link to node anymore *}
                {set $sync_nodes = $sync_event.nodes}
                {if $sync_nodes|count()}
                    {foreach $sync_nodes as $sync_node}
                        <a href={$sync_node.url|ezurl()}>{$sync_node.name|shorten(30)|wash}</a>
                        {delimiter}<br/>{/delimiter}
                    {/foreach}
                {else}
                    {$sync_event.data.name|shorten('30')|wash()}
                {/if}
            </td>
            <td>
                {* for deleted objects we have no data anymore... *}
                {$sync_event.object.class_name|wash()}
            </td>
            <td>
                {* for deleted objects we have no data anymore... *}
                {$sync_event.object.published|l10n('shortdate')}
            </td>
            <td>
                {* @todo for deleted objects, owner is not available so easily: use owner_id *}
                <a href={$sync_event.object.owner.main_node.url_alias|ezurl()} title="{$sync_event.object.owner.name|wash()}">
                    {$sync_event.object.owner.name|shorten('13')|wash()}
                </a>
            </td>
            <td>
            {if $sync_access}
                {* @todo use a different icon for in-sync events *}
                {if eq($sync_event.status, 0)}<a href="{concat( 'contentstaging/sync/', $sync_event.id )|ezurl('no')}">{/if}
                    <img src={'sync.gif'|ezimage} width="16px" height="16px" alt="{'Edit...'|i18n( 'design/admin/dashboard/all_sync_content' )}" title="{'Sync <%child_name>.'|i18n( 'design/admin/dashboard/all_sync_content',, hash( '%child_name', '...') )|wash}" />
                {if eq($sync_event.status, 0)}</a>{/if}
            {else}
                <img src={'sync-disabled.gif'|ezimage} width="16px" height="16px" alt="{'Sync...'|i18n( 'design/admin/dashboard/all_sync_content' )}" title="{'You do not have permission to sync <%child_name>.'|i18n( 'design/admin/dashboard/all_sync_content',, hash( '%child_name', '...' ) )|wash}" />
            {/if}
            </td>
        </tr>
    {/foreach}
</table>

{else}
    <p>{'Content synchronisation list is empty.'|i18n( 'design/admin/dashboard/all_sync_content' )}</p>
{/if}

{undef $sync_events $sync_access}

{/cache-block}

{undef $user_hash}