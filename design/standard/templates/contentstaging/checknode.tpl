{**
  View use to display results of "single node" sync status check
*}

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

{if ne($check_errors, '')}
    <div class="message-warning">
        {$check_errors}
    </div>
{/if}

<div class="attribute-header">
    <h1 class="long">
       {'Checking status of node'|i18n('ezcontentstaging')} <a href={$current_node.url|ezurl}>{$current_node.name|wash()}</a> {'on feed'|i18n('ezcontentstaging')} <a href={concat('contentstaging/feed/', $feed.id)|ezurl}>{$feed.name|wash}</a>
    </h1>
</div>

{if $check_results|count()}

    <table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <th>{"Differences"|i18n('ezcontentstaging')}</th>
    </tr>
    {foreach $check_results as $diff sequence array( 'bglight', 'bgdark' ) as $style}
    <tr class="{$style}">
        <td>{$diff|d18n('ezcontentstaging')}</td>
    {/foreach}
    </table>

{else}
    <div class="feedback">
        <h2>{'No differences found'|i18n('ezcontentstaging')}</h2>
    </div>
{/if}

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>
