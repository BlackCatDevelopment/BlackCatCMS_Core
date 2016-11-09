$(function() {
    jQuery.ajaxSetup({
        error: function( x, e )
        {
            //console.log('x: ',x);
            //console.log('e: ',e);
            if( x.status == 0 )           { bsErrorMsg('You are offline!\nPlease Check Your Network.');     }
            else if( x.status == 404 )    { bsErrorMsg('404 - Requested URL not found.');                   }
            else if( x.status == 500 )    { bsErrorMsg('500 - Internal Server Error.');                     }
            // dismiss parse errors
            else if( e == 'parsererror' ) { console.log('Parse error. Maybe caused by invalid JSON data.'); }
            else if( e == 'timeout' )     { bsErrorMsg('Request timed out.');                               }
            else                          { bsErrorMsg('Unknown Error.\n'+x.responseText);                  }
         }
    });
    $('input.field1').on('focusout', function() {
        $.ajax({
            type:     'POST',
            context:  $(this),
            url:      CAT_ADMIN_URL+'/tfa',
            dataType: 'json',
            data:     {
                user: this.value
            },
            cache:    false,
            success:  function(data,textStatus,jqXHR)
            {
                console.log(data);
                if(data.message === true) {
                    $('div#tfagroup').show('slow');
                } else {
                    $('div#tfagroup').hide('slow');
                }
            }
        });
    });
    $('.btn-primary').click( function(e) {
        e.preventDefault();

        // reset error message
        $('div#login-error p').html('');
        $('div#login-error').hide();

        var username_fieldname    = $('form').find('input[name=username_fieldname]').val(),
            password_fieldname    = $('form').find('input[name=password_fieldname]').val(),
            token_fieldname     = $('form').find('input[name=token_fieldname]').val(),
            dates                = {
                'username_fieldname': username_fieldname,
                'password_fieldname': password_fieldname,
                'token_fieldname'   : token_fieldname,
            };
        dates[username_fieldname]    = $('input#' + username_fieldname).val();
        dates[password_fieldname]    = $('input#' + password_fieldname).val();
        dates[token_fieldname]        = $('input#' + token_fieldname).val();

        $.ajax({
            type:     'POST',
            context:  $(this),
            url:      CAT_ADMIN_URL+'/authenticate/',
            dataType: 'json',
            data:     dates,
            cache:    false,
            success:  function( data, textStatus, jqXHR  )
            {
                if ( data.success === true )
                {
                    window.location        = data.url
                }
                else {

                    $('div#login-error p').html(data.message);
                    $('div#login-error').show();
                    $('input[name=' + password_fieldname + ']').val('').focus();
                }
            },
            error:        function( jqXHR, textStatus, errorThrown )
            {
                alert(textStatus + ': ' + jqXHR.responseText );
            }
        });
    });
});