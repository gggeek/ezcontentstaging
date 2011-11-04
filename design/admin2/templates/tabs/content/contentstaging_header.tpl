{* Content staging tab heaader for content view in admin interface *}
<li id="node-tab-contentstaging" class="{if $last}last{else}middle{/if}{if $node_tab_index|eq('contentstaging')} selected{/if}">
    {if $tabs_disabled}
        <span class="disabled" title="{'Tab is disabled, enable with toggler to the left of these tabs.'|i18n( 'design/admin/node/view/full' )}">CS...</span>
    {else}
        <a href={concat( $node_url_alias, '/(tab)/roles' )|ezurl} title="{'Show role overview.'|i18n( 'design/admin/node/view/full' )}">CS...</a>
    {/if}
</li>
