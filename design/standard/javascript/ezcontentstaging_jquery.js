
(function( $ )
{
    $( document ).ready( function()
    {
        $( 'a.ezcs-sync-node' ).click( _sync )
    });

    function _sync( e )
    {
        e.preventDefault();
        var args = $( this ).attr( 'id' ).split( '_' );
        /// @todo change link/img class to "syncing"
        $( '#syncnodelink_' + args[1] ).unbind( 'click' );
        jQuery.ez( 'ezcontentstaging::syncnode::' + args[1] + '::' + args[2], {}, _syncCallBack );
        return false;
    }

    function _syncCallBack( data )
    {
        if ( data && data.content !== '' )
        {
            /*if ( data.content.rated )
            {
                if ( data.content.already_rated )
                    $('#ezsr_changed_rating_' + data.content.id).removeClass('hide');
                else
                    $('#ezsr_just_rated_' + data.content.id).removeClass('hide');
                $('#ezsr_rating_percent_' + data.content.id).css('width', (( data.content.stats.rounded_average / 5 ) * 100 ) + '%' );
                $('#ezsr_average_' + data.content.id).text( data.content.stats.rating_average );
                $('#ezsr_total_' + data.content.id).text( data.content.stats.rating_count );
            }
            else if ( data.content.already_rated )
                $('#ezsr_has_rated_' + data.content.id).removeClass('hide');
            */
            //else alert('Invalid input variables, could not rate!');
        }
        else
        {
            // This shouldn't happen as we have already checked access in the template..
            // Unless this is inside a aggressive cache-block of course.
            alert( data.content.error_text );
        }
    }
})(jQuery);
