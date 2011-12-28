<?php /*

[ApiProvider]
ProviderClass[contentstaging]=eZContentStagingRestApiProvider

[PreRoutingFilters]
Filters[]=eZContentStagingPreventWarningsFilter

[RequestFilters]
Filters[]=eZContentStagingPreventErrorsFilter

# auto auth filter disabled, since it needs a corresponding auth filter anyway to work
#Filters[]=eZContentStagingAutoAuthFilter

# Json request decoding for POST and PUT has been implemented via patches to
# ezpRestHttpRequestParser, this filter is not needed anymore
#Filters[]=eZContentStagingJsonRequestFilter


[eZContentStagingRestContentController_CacheSettings]
ApplicationCache=disabled

[eZContentStagingRestLocationController_CacheSettings]
ApplicationCache=disabled

*/ ?>
