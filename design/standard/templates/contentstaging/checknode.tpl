{**
  View use to display results of "single node" sync status check
*}

{if ne($check_errors, '')}
    {$check_errors}
{else}
    {'Result'|i18n('contentstaging')}: {$check_results}
{/if}
