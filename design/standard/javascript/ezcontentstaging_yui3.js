
YUI( YUI3_config ).use( 'node', 'event', 'io-ez', function( Y )
{
    Y.on( "domready", function( e )
    {
        Y.all( 'a.ezcs-sync-node' ).on( 'click', _sync );
    });

    function _sync( e )
    {
        e.preventDefault();
        var args = e.currentTarget.getAttribute( 'id' ).split( '_' );
        /// @todo change link/img class to "syncing"
        Y.one( '#syncnodelink_' + args[1] ).detach( 'click', _sync );
        Y.io.ez( 'ezcontentstaging::syncnode::' + args[1] + '::' + args[2], { on : { success: _syncCallBack } } );
    }

    function _syncCallBack( id, o )
    {
        if ( o.responseJSON && o.responseJSON.content !== '' )
        {
            var data = o.responseJSON.content;
            /*if ( data.rated  )
            {
                if ( data.already_rated )
                    Y.all('#ezsr_changed_rating_' + data.id).removeClass('hide');
                else
                    Y.all('#ezsr_just_rated_' + data.id).removeClass('hide');
                Y.all('#ezsr_rating_percent_' + data.id).setStyle('width', (( data.stats.rounded_average / 5 ) * 100 ) + '%' );
                Y.all('#ezsr_average_' + data.id).setContent( data.stats.rating_average );
                Y.all('#ezsr_total_' + data.id).setContent( data.stats.rating_count );
            }
            else if ( data.already_rated  )
                Y.all('#ezsr_has_rated_' + data.id).removeClass('hide');
            //else alert('Invalid input variables, could not rate!');
            */
        }
        else
        {
            // This shouldn't happen as we have already checked access in the template..
            // Unless this is inside a aggressive cache-block of course.
            alert( o.responseJSON.error_text );
        }
    }
});
