{**
 @param string $targetId
 @param array $configurationErrors
 @param array $connectionErrors
*}

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">


{* @todo if feed obj exists, we should use its name, not id *}
<div class="attribute-header">
<h1>{'Feed check for feed'|i18n('ezcontentstaging')}: {$target_id|wash()}</h1>
</div>

{if eq($target_id, '')}
    <div class="message-warning">
        <h2>{'No feed id provided, can not check'|i18n('ezcontentstaging')}</h2>
    </div>
{else}
<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <th>{'Configuration problems'|i18n('ezcontentstaging')}</th>
    </tr>
    {if $configurationErrors|count()}
        {foreach $configurationErrors as $error sequence array( 'bglight', 'bgdark' ) as $style}
            <tr class="{$style}">
                <td>{$error|wash()}</td>
            </tr>
        {/foreach}
    {else}
        <tr class="{$style}"><td>{'No problems detected'|i18n('ezcontentstaging')}</td></tr>
    {/if}

    <tr>
        <th>{'Connection problems'|i18n('ezcontentstaging')}</th>
    </tr>
    {if $connectionErrors|count()}
        {foreach $connectionErrors as $error sequence array( 'bglight', 'bgdark' ) as $style}
            <tr class="{$style}">
                <td>{$error|wash()}</td>
            </tr>
        {/foreach}
    {else}
        <tr class="{$style}"><td>{'No problems detected'|i18n('ezcontentstaging')}</td></tr>
    {/if}
</table>
{/if}

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>