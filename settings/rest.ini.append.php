<?php /*

[Authentication]
# disabled all authentication and force admin user for now
# should be implemented perhaps in the provider
# or with a custom AuthenticationStyle
RequireAuthentication=disabled
RequireHTTPS=disabled
DefaultUserID=14

[ApiProvider]
ProviderClass[contentstaging]=contentStagingRestApiProvider

[RouteSettings]
# Skip (auth) filter for every action in 'myController' which is of API version 2
SkipFilter[]=contentStagingRestApiController_*

[contentStagingRestContentController_remove_CacheSettings]
ApplicationCache=disabled

[contentStagingRestContentController_addLocation_CacheSettings]
ApplicationCache=disabled

*/ ?>
