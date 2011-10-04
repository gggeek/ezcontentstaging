{* gg: cache-block to be reviewed - as content-altering operations might not expire it... *}
{def $user_hash = concat( $user.role_id_list|implode( ',' ), ',', $user.limited_assignment_value_list|implode( ',' ) )}
{cache-block keys=array( $user_hash )}

{* gg: could avoid showing items that are in "syncing" state... *}
{def $sync_items = fetch( 'contentstaging', 'syncitems', hash( 'limit', $block.number_of_items ) )}

<h2>{'Contents synchronization'|i18n( 'design/admin/dashboard/sync' )}</h2>

{if $sync_items}

<table class="list" cellpadding="0" cellspacing="0" border="0">
    <tr>
        {* gg: should show target_id, sync status, things to sync instead ? *}
        <th>{'Name'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th>{'Type'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th>{'Published'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th>{'Author'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th class="tight"></th>
    </tr>
    {foreach $sync_items as $sync_item sequence array( 'bglight', 'bgdark' ) as $style}
        <tr class="{$style}">
            <td>
                {* for deleted objects we habe no link to node anymore *}
                {if $sync_item.object.main_node}
                    <a href="{$sync_item.object.main_node.url_alias|ezurl('no')}" title="{$sync_item.object.name|wash()}">{$sync_item.object.name|shorten('30')|wash()}</a>
                {else}
                    {$sync_item.object.name|shorten('30')|wash()}
                {/if}
            </td>
            <td>
                {$sync_item.object.class_name|wash()}
            </td>
            <td>
                {$sync_item.object.published|l10n('shortdate')}
            </td>
            <td>
                {* @todo for deleted objects, owner is not available so easily: use owner_id *}
                <a href="{$sync_item.object.owner.main_node.url_alias|ezurl('no')}" title="{$sync_item.object.owner.name|wash()}">
                    {$sync_item.object.owner.name|shorten('13')|wash()}
                </a>
            </td>
            <td>
            {if $sync_item.can_sync}
                <a href="{concat( 'contentstaging/sync/', $sync_item.object_id, '/', $sync_item.target_id )|ezurl('no')}">
                    <img src={'sync.gif'|ezimage} width="16" height="16" alt="{'Edit'|i18n( 'design/admin/dashboard/all_sync_content' )}" title="{'Sync <%child_name>.'|i18n( 'design/admin/dashboard/all_sync_content',, hash( '%child_name', $sync_node.object.name) )|wash}" />
                </a>
            {else}
                <img src="{'sync-disabled.gif'|ezimage('no')}" alt="{'Sync'|i18n( 'design/admin/dashboard/all_sync_content' )}" title="{'You do not have permission to sync <%child_name>.'|i18n( 'design/admin/dashboard/all_sync_content',, hash( '%child_name', $sync_node.object.name ) )|wash}" />
            {/if}
            </td>
        </tr>
    {/foreach}
</table>

{else}

<p>{'Content synchronisation list is empty.'|i18n( 'design/admin/dashboard/all_sync_content' )}</p>

{/if}

{undef $sync_items}

{/cache-block}

{undef $user_hash}