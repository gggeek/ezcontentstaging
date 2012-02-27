{**
 @param array of eZContentStagingEvent $feeds

 @todo add support for pagination
 @todo if $manage_sync_access is true, allow user to add/edit/remove feeds
 @todo show  umber of pending evens for every feed
*}

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

{if is_set( $action_results )}
    <div class="{if $action_errors|count()}message-warning{else}message-feedback{/if}">
        <h2>{concat("Feed ", $action, " action results")|d18n('ezcontentstaging')}:</h2>
        {if $action_errors|count()}
            <ul>
            {foreach $action_errors as $msg}
                <li>{$msg|wash()}</li>
            {/foreach}
            </ul>
        {/if}
        <ul>
            {foreach $action_results as $msg}
                <li>{$msg|wash()}</li>
            {/foreach}
        </ul>
    </div>
{/if}

<div class="attribute-header">
<h1>{'Synchronization feeds'|i18n('ezcontentstaging')}</h1>
</div>

{def $manage_sync_access = fetch( 'user', 'has_access_to', hash( 'module', 'contentstaging', 'function', 'manage' ) )
     $source = false()
     $events_count = false()}
<form action={'contentstaging/feeds'|ezurl()} method="post">
<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        {if $manage_sync_access}
        <th></th>
        {/if}
        <th>{'Name'|i18n('ezcontentstaging')}</th>
        <th>{'Events'|i18n('ezcontentstaging')}</th>
        <th>{'Sources'|i18n('ezcontentstaging')}</th>
        <th>{'Description'|i18n('ezcontentstaging')}</th>
        <th>{'Status'|i18n('ezcontentstaging')}</th>
    </tr>
    {foreach $feeds as $id => $feed sequence array( 'bglight', 'bgdark' ) as $style}
        {set $events_count = fetch( 'contentstaging', 'sync_events_count', hash( 'target_id', $id ) )}
        <tr class="{$style}">
            {*$feed|attribute(show)*}
            {if $manage_sync_access}
            <td align="left" width="1"><input type="checkbox" name="feeds[]" value="{$id}" /></td>
            {/if}
            <td><a href={concat('contentstaging/feed/', $id)|ezurl()}>{$feed.name|wash()}</a></td>
            <td>{$events_count}</td>
            <td>
                {* @todo better display of feed source node: fetch it *}
                {foreach $feed.subtrees as $nodeid}
                    {set $source = fetch( 'content', 'node', hash( 'node_id', $nodeid ) )}
                    {if $source}
                        <a href={$source.url|ezurl()} title="{'Node ID:'|i18n('ezcontentstaging')} {$source.node_id}">{$source.name|wash()}</a><br/>
                    {else}
                        {'Missing node'|i18n('ezcontentsatging')}: {$nodeid}<br/>
                    {/if}
                {/foreach}
            </td>
            <td>{$feed.description|wash()}</td>
            <td><a href={concat('contentstaging/checkfeed/', $id)|ezurl()}>{'check'|i18n('ezcontentsatging')}</a></td>
        </tr>
    {/foreach}
</table>
{if $manage_sync_access}
    {* @todo ... *}
    <input class="button" type="submit" name="ResetFeedsButton" value="{'Reset feeds'|i18n('ezcontentstaging')}" />
    <input class="button" type="submit" name="InitializeFeedsButton" value="{'Initialize feeds'|i18n('ezcontentstaging')}" />
{/if}
</form>
{undef $manage_sync_access $source $events_count}

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>