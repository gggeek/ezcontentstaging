{**
  @param array of string syncErrors
  @param array of string syncResults (in case action taken)

  @todo show more clearly items in "syncing" status
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
    if ( document.syncaction.selectall.value == "{/literal}{'Select all'|i18n('design/standard/staging/sync')}{literal}" )
    {
        document.syncaction.selectall.value = "{/literal}{'Deselect all'|i18n('design/standard/staging/sync')}{literal}";
        with (document.syncaction)
        {
            for (var i=0; i < elements.length; i++)
            {
                if (elements[i].type == 'checkbox' && elements[i].name == 'SyncIDArray[]')
                     elements[i].checked = true;
            }
        }
     }
     else
     {

         document.syncaction.selectall.value = "{/literal}{'Select all'|i18n('design/standard/staging/sync')}{literal}";
         with (document.syncaction)
        {
            for (var i=0; i < elements.length; i++)
            {
                if (elements[i].type == 'checkbox' && elements[i].name == 'SyncIDArray[]')
                     elements[i].checked = false;
            }
         }
     }
}
{/literal}
//-->
</script>
{def $sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'sync' ) )}

{if $target_id}
  Feed: {$target_id} ...
{/if}

{* @todo... *}
{if is_set( $syncResults )}
	<div class="message-warning">
		<h2>{"Content synchronisation action results "|i18n("design/admin/class/edit")}:</h2>
		<ul>
			{foreach $syncResults as $evtMsg}
				<li>{$evtMsg}</li>
			{/foreach}
		</ul>
	</div>
{/if}

{def $page_limit = 30}
{if ne($target_id, '')}
    {def $list_count = fetch( 'contentstaging', 'syncitems_count', hash( 'target_id', $target_id ) )}
{else}
    {def $list_count = fetch( 'contentstaging', 'syncitems_count' )}
{/if}

{* @todo add view params to the form target url ? *}
<form name="syncaction" action={concat( "contentstaging/feed/", $target_id )|ezurl} method="post" >

<div class="attribute-header">
    <h1 class="long">{"My syncs..."|i18n("design/standard/staging/sync")}</h1>
</div>

{if $list_count}

<p>
    {"These are the current objects you are working on (... not really ...). You can push content to the destination server."|i18n("design/standard/staging/sync")|nl2br}
</p>

<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
    {if $sync_access}
    <th></th>
    {/if}
    <th>{"Name"|i18n("design/standard/staging/sync")}</th>
    <th>{"Language"|i18n("design/standard/staging/sync")}</th>
    <th>{"Last modified"|i18n("design/standard/staging/sync")}</th>
    <th>{"Synchronisation state"|i18n("design/standard/staging/sync")}</th>
    {* @todo do display feed name when $target_id is empty *}
    {if $sync_access}
    <th>{"Action"|i18n("design/standard/staging/sync")}</th>
    {/if}
</tr>
{if ne($target_id, '')}
    {def $item_list = fetch( 'contentstaging', 'syncitems', hash( 'target_id', $target_id,
                                                                  'limit', $page_limit,
                                                                  'offset', $view_parameters.offset ) )}
{else}
    {def $item_list = fetch( 'contentstaging', 'syncitems', hash( 'limit', $page_limit,
                                                                  'offset', $view_parameters.offset ) )}
{/if}

{foreach $item_list as $sync_item sequence array( 'bglight', 'bgdark' ) as $style}
<tr class="{$style}">
    {if $sync_access}
    <td align="left" width="1">
        <input type="checkbox" name="SyncArray[]" value="{concat( $sync_item.target_id, '_', $sync_item.object_id )}" {if ne($sync_item.status, 0)}disabled="disabled"{/if}/>
    </td>
    {/if}
    <td>
        <a href={$sync_item.object.main_node.url_alias}>{$sync_item.object.name|wash}</a>
    </td>
    <td>
        {$sync_item.object.initial_language.name|wash}
    </td>
    <td>
        {*$sync_item.*}...
    </td>
	<td>
        {$sync_item.object.modified|wash}
    </td>
    {if $sync_access}
    <td width="1">
        {if eq($sync_item.status, 0)}<a href={concat("/contentstaging/sync/", $sync_item.object.id, '/', $sync_item.target_id )|ezurl}><img src={"websitetoolbar/sync.gif"|ezimage} width="16px" height="16px" alt="{'Sync'|i18n('design/standard/staging/sync')}" /></a>{/if}
    </td>
    {/if}
</tr>
{/foreach}
</table>

{undef $list_count}

{if $sync_access}
<input class="button" name="selectall" onclick="checkAll()" type="button" value="{'Select all'|i18n('design/standard/staging/sync')}" />
<input class="button" name="syncAction" type="button" onclick=submit() value="{'Run'|i18n('design/standard/staging/sync')}" />
{/if}

{include uri='design:navigator/google.tpl'
         page_uri=concat('/contentstaging/feed/', $target_id)
         item_count=$list_count
         view_parameters=$view_parameters
         item_limit=$page_limit}

{else}

<div class="feedback">
<h2>{"You have no syncs..."|i18n("design/standard/staging/sync")}</h2>
</div>

{/if}

</form>

{undef $sync_access $list_count}

</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>