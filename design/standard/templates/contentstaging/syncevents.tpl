{**
  View use to display results of "single item" content sync
  (nb: single item might be synced to many targets in one go)

  @param array of eZContentStagingEvent $current_node_events
  @param array of eZContentStagingEvent $related_node_events
  @param array of string sync_errors
  @param array of string sync_results
*}

{def $create_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'sync' ) )}

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<div class="content-sync">
<div class="attribute-header">
    <h1 class="long">
        {"Node synchronisation to the target "|i18n("ezcontentstaging")} "{$target_id}"
    </h1>
</div>
{if or(is_set( $sync_results ), is_set( $sync_errors ))}
    {if $sync_errors|count()}
    <div class="message-error">
        <h2>{"Error : content synchronisation action results "|i18n("design/admin/class/edit")}:</h2>
        <ul>
        {foreach $sync_errors as $msg}
            <li>{$msg|wash()}</li>
        {/foreach}
        </ul>
    </div>
    {/if}
    <p class="clear"></p>
    {if $sync_results|count()}
    <div class="message-warning">
        <h2>{"Content synchronisation action results "|i18n("design/admin/class/edit")}:</h2>
        <ul>
        {foreach $$sync_results as $msg}
            <li>{$msg|wash()}</li>
        {/foreach}
        </ul>
    </div>
    {/if}
{/if}

{if or($sync_errors|count(), $sync_results|count()|eq(0) )}
<form name="syncaction" action={"contentstaging/syncevents/"|ezurl} method="post" >
<input type="hidden" name="NodeID" value="{$current_node.node_id}" />
<input type="hidden" name="TargetId" value="{$target_id}" />

{if and($create_sync_access, count($current_node_events)|gt(0) ) }
<h2>{"Current node details"|i18n('ezcontentstaging')} :</h2>
<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <th width="30%">{"Name"|i18n('ezcontentstaging')}</th>
        <th width="25%">{"Language..."|i18n('ezcontentstaging')}</th>
        <th width="15%">{"Date"|i18n('ezcontentstaging')}</th>
        <th width="30%">{"Events"|i18n('ezcontentstaging')}</th>
    </tr>
    <tr class="{$style}">
        <td>
            {$current_node.name|wash()}
        </td>
        <td>
            {$current_node.object.initial_language.name|wash}
        </td>
        <td>
            {$current_node.object.modified|l10n('shortdate')}
        </td>
        <td>
            {foreach $current_node_events as $sync_item sequence array( 'bglight', 'bgdark' ) as $style}
            {$sync_item.id} - {$sync_item.to_sync_string}<br />
            {/foreach}
        </td>
    </tr>
</table>
{/if}

{if and(count($sync_related_objects)|gt(0), $create_sync_access, count($related_node_events)|gt(0) ) }
<h2>{"Related node details"|i18n('ezcontentstaging')} :</h2>
<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <th width="30%">{"Name"|i18n('ezcontentstaging')}</th>
        <th width="25%">{"Language..."|i18n('ezcontentstaging')}</th>
        <th width="15%">{"Date"|i18n('ezcontentstaging')}</th>
        <th width="30%">{"Events"|i18n('ezcontentstaging')}</th>
    </tr>
    {foreach $sync_related_objects as $sync_related_object sequence array( 'bglight', 'bgdark' ) as $style}
    <tr class="{$style}">
        <td>
            {$sync_related_object.name|wash()}
        </td>
        <td>
            {$sync_related_object.initial_language.name|wash}
        </td>
        <td>
            {$sync_related_object.modified|l10n('shortdate')}
        </td>
        <td>
            {foreach $related_node_events[$sync_related_object.id] as $sync_item sequence array( 'bglight', 'bgdark' ) as $style}
            {$sync_item.id} - {$sync_item.to_sync_string}<br />
            {/foreach}
        </td>
    </tr>
    {/foreach}
</table>
{/if}

{if and($create_sync_access, count($current_node_events)|gt(0))}
    <input class="button" name="ConfirmSyncButton" type="submit" value="{'Confirm the synchronization of all above contents'|i18n("ezcontentstaging")}" />
    <input class="button" name="CancelButton" type="submit" value="{'Cancel the synchronisation'|i18n("ezcontentstaging")}" />
{/if}
</form>
{else}
    <a href={$current_node.url_alias|ezurl()} title="{$current_node.name}">{"Back to the content "|i18n("ezcontentstaging")}"{$current_node.name}"</a>
{/if}
{undef $create_sync_access}

</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>
