{**
  View use to display results of "single item" content sync
  (nb: single item might be synced to many targets in one go)

  @param array of eZContentStagingEvent $sync_events
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
        {"Single node synchronisation"|i18n("contentstaging")}
    </h1>
</div>
{if or(is_set( $sync_results ), is_set( $sync_errors ))}
    <div class="message-warning">
        <h2>{"Content synchronisation action results "|i18n("design/admin/class/edit")}:</h2>
        {* @todo mark in red *}
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

<div class="attribute-header">
    <p><b><u>{"Synchronisation details"|i18n("contentstaging")}</u></b></p>
    <p><b>{"Node name"|i18n("contentstaging")} : </b>{$$current_node.name}</p>
	<p><b>{"Target"|i18n("contentstaging")} : </b>{$target_id}</p>
</div>

<form name="syncaction" action={"contentstaging/syncnode/"|ezurl} method="post" >
<input type="hidden" name="NodeID" value="{$current_node.node_id}" />
<input type="hidden" name="TargetId" value="{$target_id}" />

{if and(count($sync_related_objects)|gt(0), $create_sync_access, count($sync_events)|gt(0) ) }
	<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<th>{"Name"|i18n("contentstaging")}</th>
			<th>{"Language..."|i18n("contentstaging")}</th>
			<th>{"Date"|i18n("contentstaging")}</th>
		</tr>
		{foreach $sync_related_objects as $sync_item sequence array( 'bglight', 'bgdark' ) as $style}
		<tr class="{$style}">
			<td>
				{$sync_item.name|wash()}
			</td>
			<td>
				{$sync_item.initial_language.name|wash}
			</td>
			<td>
				{$sync_item.modified|l10n('shortdate')}
			</td>
		</tr>
		{/foreach}
	</table>
{/if}

{if and($create_sync_access, count($sync_events)|gt(0))}
	<input class="button" name="ConfirmSyncNodeButton" type="submit" value="{'Confirm sync'|i18n(' ')}" />
{else}
	<a href={$current_node.url_alia|ezurl()}>{"Back to the page"|ez18n('contentstaging')}  {$current_node.name}</a>
{/if}
</form>

{undef $create_sync_access}

</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>