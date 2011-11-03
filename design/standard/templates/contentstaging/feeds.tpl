{**
 @param array of eZContentStagingEvent $feeds

 @todo add support for pagination
 @todo if $manage_sync_access is true, allow user to add/edit/remove feeds
 @todo show  umber of pending evens for every feed
*}

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

Title: synchronization feeds...
{def $manage_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'manage' ) )
     $source = false()}
<form action={'contentstaging/feeds'|ezurl()} method="post">
<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        {if $manage_sync_access}
        <th></th>
        {/if}
        <th>{'Name'|i18n('ezcontentstaging')}</th>
        <th>{'Sources'|i18n('ezcontentstaging')}</th>
        <th>{'Description'|i18n('ezcontentstaging')}</th>
    </tr>
    {foreach $feeds as $id => $feed sequence array( 'bglight', 'bgdark' ) as $style}
        <tr class="{$style}">
            {*$feed|attribute(show)*}
            {if $manage_sync_access}
            <td align="left" width="1"><input type="checkbox" name="feeds[]" value="{$id}" /></td>
            {/if}
            <td><a href={concat('contentstaging/feed/', $id)|ezurl()}>{$feed.name|wash()}</a></td>
            <td>
                {* @todo better display of feed source node: fetch it *}
                {foreach $feed.subtrees as $nodeid}
                    {set $source = fetch( 'content', 'node', hash( 'node_id', $nodeid ) )}
                    {if $source}
                        <a href={$source.url|ezurl()}>{$source.name|wash()}</a><br/>
                    {else}
                        {'Missing node'|i18n()}: {$nodeid}<br/>
                    {/if}
                {/foreach}
            </td>
            <td>{$feed.description|wash()}</td>
        </tr>
    {/foreach}
</table>
{if $manage_sync_access}
    {* @todo ... *}
    <input class="button" type="submit" name="resetFeedAction" value="Reset feeds..." />
    <input class="button" type="submit" name="initailizeFeedAction" value="Initialize feeds..." />
{/if}
</form>
{undef $manage_sync_access $source}

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>