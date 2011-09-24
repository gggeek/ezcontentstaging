<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<div class="content-sync">

<script type="text/javascript">
<!--
{literal}
function checkAll()
{
{/literal}
    if ( document.syncaction.selectall.value == "{'Select all'|i18n('design/standard/staging/sync')}" )
{literal}
    {
{/literal}
        document.syncaction.selectall.value = "{'Deselect all'|i18n('design/standard/staging/sync')}";
{literal}
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
{/literal}
         document.syncaction.selectall.value = "{'Select all'|i18n('design/standard/staging/sync')}";
{literal}
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

{if is_set($syncIDResultArray)}
	<div class="message-warning">
		<h2>{"Content synchronisation action results "|i18n("design/admin/class/edit")}:</h2>
		<ul>
			{foreach $syncIDResultArray as $syncId => $evtMsg}
				<li>{$evtMsg}</li>
			{/foreach}
		</ul>
	</div>
{/if}

{def $page_limit=30
	{def $list_count = fetch('contentstaging', 'sync_count' , hash( 'parent_node_id',   ezini( 'NodeSettings', 'RootNode', 'content.ini' ),
													   'limit',            $block.number_of_items,
													   'main_node_only',   true(),
													   'attribute_filter', array( array( 'synchronization_state', '!=', 'synchronized' ) )
														) )}

<form name="syncaction" action={concat("content/sync/")|ezurl} method="post" >

<div class="attribute-header">
    <h1 class="long">{"My syncs"|i18n("design/standard/staging/sync")}</h1>
</div>

{if $list_count}

<p>
    {"These are the current objects you are working on. You can push content to the destination server."|i18n("design/standard/staging/sync")|nl2br}
</p>

<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
    <th></th>
    <th>{"Name"|i18n("design/standard/staging/sync")}</th>
    <th>{"Language"|i18n("design/standard/staging/sync")}</th>
    <th>{"Last modified"|i18n("design/standard/staging/sync")}</th>
    <th>{"Synchronisation state"|i18n("design/standard/staging/sync")}</th>
    <th>{"Action"|i18n("design/standard/staging/sync")}</th>
</tr>

{foreach fetch( 'content', 'contentstaging', hash( 'parent_node_id',   ezini( 'NodeSettings', 'RootNode', 'content.ini' ),
                                                           'limit',            $block.number_of_items,
                                                           'main_node_only',   true(),
                                                           'attribute_filter', array( array( 'synchronization_state', '!=', 'synchronized' ) )
                                                            ) ) as $sync
         sequence array(bglight,bgdark) as $style}
<tr class="{$style}">
    <td align="left" width="1">
        <input type="checkbox" name="SyncIDArray[]" value="{$sync.id}" />
    </td>
    <td>
        <a href={$sync.url_alias}>{$sync.name|wash}</a>
    </td>
    <td>
        {$sync.initial_language.name|wash}
    </td>
    <td>
        {$sync.synchronization_state|l10n(datetime)}
    </td>
	<td>
        {$sync.modified|wash}
    </td>
    <td width="1">
        <a href={concat("/staging/controlpanel/",$sync.contentobject.id)|ezurl}><img src={"websitetoolbar/sync.gif"|ezimage} width="16px" height="16px" alt="{'Sync'|i18n('design/standard/staging/sync')}" /></a>
    </td>
</tr>
{/foreach}
</table>
<input class="button" name="selectall" onclick=checkAll() type="button" value="{'Select all'|i18n('design/standard/staging/sync')}" />
<input class="button" name="syncrun" type="button" onclick=submit() value="{'Run'|i18n('design/standard/staging/sync')}" />
{include name=navigator
         uri='design:navigator/google.tpl'
         page_uri='/staging/controlpanel'
         item_count=$list_count
         view_parameters=$view_parameters
         item_limit=$page_limit}

{else}

<div class="feedback">
<h2>{"You have no syncs"|i18n("design/standard/staging/sync")}</h2>
</div>

{/if}

</form>

</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>