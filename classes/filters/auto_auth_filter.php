<?php

class contentStagingAutoAuthFilter implements ezpRestRequestFilterInterface
{
    protected $controllerClass;

    public function __construct( ezcMvcRoutingInformation $routeInfo, ezcMvcRequest $request )
    {
        $this->controllerClass = $routeInfo->controllerClass;
    }

    public function filter()
    {
        $ini = eZINI::instance( 'contentstagingtarget.ini' );
        $controllers = $ini->variable( 'RestAutoAuthFilter', 'AutoAuthControllers' );
        if ( in_array( $this->controllerClass, $controllers ) )
        {
            /// @todo take care: what if user does not exist?
            $user = eZUser::fetch( $ini->variable( 'RestAutoAuthFilter', 'UserID' ) );
            $user->loginCurrent();
        }

    }

}

?>
