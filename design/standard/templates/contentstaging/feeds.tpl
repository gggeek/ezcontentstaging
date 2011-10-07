{**
 @param array of eZContentStagingEvent $feeds

 @todo add support for pagination
 @todo if $manage_sync_access is true, allow user to add/edit/remove feeds
*}

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

Title: synchronization feeds...
{def $manage_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'manage' ) )}
<form action={'contentstaging/feeds'|ezurl()} method="post">
<table>
    <tr>
        {if $manage_sync_access}
        <th></th>
        {/if}
        <th>Name...</th>
        <th>Sources...</th>
    </tr>
    {foreach $feeds as $id => $feed}
        <tr>
            {*$feed|attribute(show)*}
            {if $manage_sync_access}
            <th><input type="checkbox" name="feeds[]" value="{$id}" /></th>
            {/if}
            <td><a href={concat('contentstaging/feed/', $id)|ezurl()}>{$feed.name|wash()} {$id}</a></td>
            <td>
                {* @todo better display of feed source node: fetch it *}
                {foreach $feed.subtrees as $nodeid}
                    <a href={concat('content/view/full/', $nodeid)|ezurl()}>{$id}</a><br/>
                {/foreach}
            </td>
        </tr>
    {/foreach}
</table>
{if $manage_sync_access}
    {* @todo ... *}
    <input type="submit" name="resetFeedAction" value="Reset feeds..." />
    <input type="submit" name="initailizeFeedAction" value="Initialize feeds..." />
{/if}
</form>
{undef $manage_sync_access}

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>