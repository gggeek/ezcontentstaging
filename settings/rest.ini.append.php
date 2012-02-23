<?php /*

[ApiProvider]
ProviderClass[contentstaging]=eZContentStagingRestApiProvider

[RouteSettings]
SkipFilter[]=eZContentStagingRestUserController_createSession

[PreRoutingFilters]
Filters[]=eZContentStagingPreventWarningsFilter

[RequestFilters]
Filters[]=eZContentStagingPreventErrorsFilter

[eZContentStagingRestContentController_CacheSettings]
ApplicationCache=disabled

[eZContentStagingRestLocationController_CacheSettings]
ApplicationCache=disabled

[eZContentStagingRestUserController_CacheSettings]
ApplicationCache=disabled

*/ ?>
