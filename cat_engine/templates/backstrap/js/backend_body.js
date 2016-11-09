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

    /* show javascript errors as modal */
    function bsErrorMsg(message)
    {
        $('#bsDialog .modal-title').html('<div class="text-danger"><i class="fa fa-fw fa-warning"></i> '+cattranslate('Sorry, there was an error')+'</div>');
        $('#bsDialog .modal-body').html('<div class="text-danger">'+cattranslate(message)+'</div>');
        $('#bsDialog').modal('show');
    }

    /* handle session timeout and re-login */
    function CATSessionTimedOut()
    {
        $('#bsSessionTimedOutDialog').modal('show');
        $('#bsSessionToFE').unbind('click').on('click',function(e) {
            e.preventDefault();
            window.location.replace(CAT_URL); // also removes history
        });
        $('button#bsSessionLogin').unbind('click').on('click',function(e) {
            $('div#login-error').text('').hide(); // make sure there is no old error
            var ufield = $('input.form-control.u').prop('id');
            var pfield = $('input.form-control.p').prop('id');
            var dates  = {
                'username_fieldname': $('input.form-control.u').prop('id'),
                'password_fieldname': $('input.form-control.p').prop('id'),
            };
            dates[ufield] = $('input.form-control.u').val();
            dates[pfield] = $('input.form-control.p').val();
            $.ajax({
                type    : 'POST',
                url     : CAT_ADMIN_URL+'/authenticate',
                dataType: 'json',
                data    : dates,
                success : function(data, status) {
                    if(data.success === false)
                    {
                        $('div#login-error').text(data.message).show();
                    }
                    else
                    {
                        $('#bsSessionTimedOutDialog').modal('hide');
                        // reset session timer
                        CATSessionSetTimer(sess_time,CATSessionTimedOut,'span#sesstime','sesstimealert');
                    }
                }
            });
            e.preventDefault();
        });
    }

    // really disable disabled links
    $('a.disabled').on('click',function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    });

    // handle nested tabs
    $("ul.nav-tabs.inner a").click(function (e) {
      e.preventDefault();
        $(this).tab('show');
    });

    // add X-Editable to elements with 'editable' class
    $('.editable').editable();

    // add tooltip
    $('[data-title!=""]').qtip({
        content: { attr: 'data-title' },
        style: { classes: 'qtip-bootstrap' }
    });

    // page tree off-canvas toggle
    $('[data-toggle=offcanvas]').click(function() {
        $('.row-offcanvas').toggleClass('active');
        $(this).find('i').toggleClass('fa-caret-right').toggleClass('fa-caret-left');
    });

    // page tree caret toggle
    $('div#sidebar li span').on('click',function(e) {
        $(this).toggleClass('fa-caret-down').toggleClass('fa-caret-right');
    });

    // page tree hover tooltips
    $('.hasTooltip').each(function() { // Notice the .each() loop, discussed below
        $(this).qtip({
            content: {
                text: $(this).next('div').html() // Use the "div" element next to this for the content
            },
            style: { classes: 'qtip-bootstrap' }
        });
    });

    // detach = move to region header
    $('.detach').each( function() {
        $(this).detach()
               .appendTo('section#subheader div#contextual');
    });

    // format buttons
    $('input[type="submit"]').addClass('btn btn-primary');
    $('input[type="reset"]').addClass('btn btn-default');
    $('input[type="button"]').addClass('btn btn-default');

    // avoid modal contents to be sent more than once
    $('body').on('hidden.bs.modal', '.modal', function() {
        $(this).removeData('bs.modal');
    });

    // add session timer
    var sess_time = CATTimeStringToSecs($('div#sessiontimer span#sesstime').text());
    CATSessionSetTimer(sess_time,CATSessionTimedOut,'span#sesstime','sesstimealert');

});