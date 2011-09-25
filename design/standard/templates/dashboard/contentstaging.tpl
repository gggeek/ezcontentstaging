{* gg: cache-block to be reviewed - as content-altering operations might not expire it... *}
{def $user_hash = concat( $user.role_id_list|implode( ',' ), ',', $user.limited_assignment_value_list|implode( ',' ) )}
{cache-block keys=array( $user_hash )}

{* We only fetch items within last 60 days to make sure we don't generate to heavy sql queries *}
{* gg: not a good idea to hardcode such a limit here... *}
{def $sync_items = fetch( 'contentstaging', 'sync', hash( 'parent_node_id',   ezini( 'NodeSettings', 'RootNode', 'content.ini' ),
                                                         'limit',            $block.number_of_items,
                                                         'main_node_only',   true(),
                                                         'attribute_filter', array( array( 'synchronization_state', '=', false ) )
                                                       ) )}

<h2>{'Contents synchronization'|i18n( 'design/admin/dashboard/sync' )}</h2>

{if $sync_items}

<table class="list" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <th>{'Name'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th>{'Type'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th>{'Published'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th>{'Author'|i18n( 'design/admin/dashboard/all_sync_content' )}</th>
        <th class="tight"></th>
    </tr>
    {foreach $sync_items as $sync_node sequence array( 'bglight', 'bgdark' ) as $style}
        <tr class="{$style}">
            <td>
                <a href="{$sync_node.url_alias|ezurl('no')}" title="{$sync_node.name|wash()}">{$sync_node.name|shorten('30')|wash()}</a>
            </td>
            <td>
                {$sync_node.class_name|wash()}
            </td>
            <td>
                {$sync_node.object.published|l10n('shortdate')}
            </td>
            <td>
                <a href="{$sync_node.object.owner.main_node.url_alias|ezurl('no')}" title="{$sync_node.object.owner.name|wash()}">
                    {$sync_node.object.owner.name|shorten('13')|wash()}
                </a>
            </td>
            <td>
            {if $sync_node.can_sync}
                <a href="{concat( 'content/sync/', $sync_node.contentobject_id )|ezurl('no')}">
                    <img src={'sync.gif'|ezimage} width="16" height="16" alt="{'Edit'|i18n( 'design/admin/dashboard/all_sync_content' )}" title="{'Sync <%child_name>.'|i18n( 'design/admin/dashboard/all_sync_content',, hash( '%child_name', $²sync_node.name) )|wash}" />
                </a>
            {else}
                <img src="{'sync-disabled.gif'|ezimage('no')}" alt="{'Sync'|i18n( 'design/admin/dashboard/all_sync_content' )}" title="{'You do not have permission to edit <%child_name>.'|i18n( 'design/admin/dashboard/all_sync_content',, hash( '%child_name', $child_name ) )|wash}" />
            {/if}
            </td>
        </tr>
    {/foreach}
</table>

{else}

<p>{'Content synchonisation list is empty.'|i18n( 'design/admin/dashboard/all_sync_content' )}</p>

{/if}

{undef $sync_items}

{/cache-block}

{undef $user_hash}