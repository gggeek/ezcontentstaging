{**
  @param array of string sync_errors
  @param array of string sync_results (in case action taken)
  @param string target_id
  @param array view_parameters

  @todo items in "syncing" status should not be selectable for sync via js "select all"
  @todo move to admin2 style for usage of "box" divs?
*}
<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<div class="content-sync">

<script type="text/javascript">
<!--
{literal}
function checkAll()
{
    if ( document.syncaction.selectall.value == "{/literal}{'Select all'|i18n('ezcontentstaging')}{literal}" )
    {
        document.syncaction.selectall.value = "{/literal}{'Deselect all'|i18n('ezcontentstaging')}{literal}";
        with (document.syncaction)
        {
            for (var i=0; i < elements.length; i++)
            {
                if (elements[i].type == 'checkbox' && elements[i].name == 'syncArray[]')
                     elements[i].checked = true;
            }
        }
    }
    else
    {
        document.syncaction.selectall.value = "{/literal}{'Select all'|i18n('ezcontentstaging')}{literal}";
        with (document.syncaction)
        {
            for (var i=0; i < elements.length; i++)
            {
                if (elements[i].type == 'checkbox' && elements[i].name == 'syncArray[]')
                    elements[i].checked = false;
            }
        }
    }
}
{/literal}
//-->
</script>

{if is_set( $sync_results )}
    <div class="message-warning">
        <h2>{"Content synchronisation action results "|i18n('ezcontentstaging')}:</h2>
        {* @todo mark in  red *}
        {if $sync_errors|count()}
            <ul>
            {foreach $sync_errors as $msg}
                <li>{$msg|wash()}</li>
            {/foreach}
            </ul>
        {/if}
        <ul>
            {foreach $sync_results as $msg}
                <li>{$msg|wash()}</li>
            {/foreach}
        </ul>
    </div>
{/if}

<div class="attribute-header">
    <h1 class="long">
        {if $target_id}
            {"Feed"|i18n('ezcontentstaging')}: {$target_id|wash()} ...
        {else}
            {"Feeds"|i18n('ezcontentstaging')}
        {/if}
    </h1>
</div>

{def $page_limit = 30
     $sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'sync' ) )
     $manage_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'manage' ) )
     $sync_nodes = array()}
{if ne($target_id, '')}
    {def $list_count = fetch( 'contentstaging', 'sync_events_count', hash( 'target_id', $target_id ) )}
{else}
    {def $list_count = fetch( 'contentstaging', 'sync_events_count' )}
{/if}
{if $list_count}

    <p>
        {"These are the events in need of sync.... You can push them to the destination server."|i18n('ezcontentstaging')|nl2br}
    </p>

    {* @todo add view params to the form target url ? *}
    <form name="syncaction" action={concat( "contentstaging/feed/", $target_id )|ezurl} method="post" >
    {if ne($target_id, '')}
        {def $item_list = fetch( 'contentstaging', 'sync_events', hash( 'target_id', $target_id,
                                                                      'limit', $page_limit,
                                                                      'offset', $view_parameters.offset ) )}
    {else}
        {def $item_list = fetch( 'contentstaging', 'sync_events', hash( 'limit', $page_limit,
                                                                      'offset', $view_parameters.offset ) )}
    {/if}

    <table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        {if $sync_access}
            <th></th>
        {/if}
        <th>{"Status"|i18n('ezcontentstaging')}</th>
        <th>{"Content"|i18n('ezcontentstaging')}</th>
        <th>{"Date"|i18n('ezcontentstaging')}</th>
        <th>{"Modified"|i18n('ezcontentstaging')}</th>
        <th>{"Language"|i18n('ezcontentstaging')}</th>
    </tr>
    {foreach $item_list as $sync_item sequence array( 'bglight', 'bgdark' ) as $style}
    <tr class="{$style}">
        {if $sync_access}
        <td align="left" width="1">
            <input type="checkbox" name="syncArray[]" value="{$sync_item.id}" {if ne($sync_item.status, 0)}disabled="disabled"{/if}/>
        </td>
        {/if}
        <td>
            {if eq($sync_item.status, 1)}
                <img src={"sync-executing.gif"|ezimage} width="16px" height="16px" alt="{'Sync ongoing...'|i18n('ezcontentstaging')}" />
            {else}
                <img src={"sync.gif"|ezimage} width="16px" height="16px" alt="{'Sync...'|i18n('ezcontentstaging')}" />
            {/if}
        </td>
        <td>
            {* nb: for deleted objects we have no link to node anymore *}
            {set $sync_nodes = $sync_item.nodes}
            {if $sync_nodes|count()}
                {foreach $sync_nodes as $sync_node}
                    <a href={$sync_node.url|ezurl()}>{$sync_node.name|wash}</a>
                    {delimiter}<br/>{/delimiter}
                {/foreach}
            {else}
                {$sync_item.data.name|wash()}
            {/if}
        </td>
        <td>
            {$sync_item.modified|l10n('shortdatetime')}
        </td>
        <td>
            {concat('action_', $sync_item.to_sync)|d18n('ezcontentstaging')}
        </td>
        <td>
            {$sync_item.language.locale}
        </td>
    </tr>
    {/foreach}
    </table>

    {if $sync_access}
        <input class="button" name="selectall" onclick="checkAll()" type="button" value="{'Select all'|i18n('ezcontentstaging')}" />
        <input class="button" name="SyncEventsButton" type="submit" value="{'Synchronize'|i18n('ezcontentstaging')}" />
    {/if}
    {if $manage_access}
        <input class="button" name="RemoveEventsButton" type="submit" value="{'Remove'|i18n('ezcontentstaging')}" />
    {/if}

    </form>

    {include uri='design:navigator/google.tpl'
             page_uri=concat('/contentstaging/feed/', $target_id)
             item_count=$list_count
             view_parameters=$view_parameters
             item_limit=$page_limit}

{else}
    <div class="feedback">
        <h2>{"No pending synchronization events"|i18n('ezcontentstaging')}</h2>
    </div>
{/if}

{undef $sync_access $manage_access $list_count $page_limit $sync_nodes}

</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>