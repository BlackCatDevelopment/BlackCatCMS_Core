$(function() {
    var dashboard_id = $('div.dashboard').data('id'),
        columns      = $('div.dashboard').data('columns');

    // dashboard lanes
    $('ul.columnize').columnize(
        columns,
        {
            ul_classes:'bs_sortable'
        }
    );

    // addable widgets
    $.ajax({
        type    : 'POST',
        url     : CAT_ADMIN_URL + '/dashboard/widgets/' + dashboard_id,
        data    : {},
        dataType: 'json',
        success : function(data, status) {
            // activate for debugging:
            // console.log('data',data);
            // if there are widgets that are not yet an the dashboard...
            if(data.data && data.data.length)
            {
                var select = $('div#bs_available_widgets select#dashboard_add_widget');
                var index  = 0;
                for(index in data.data) {
                    var obj = data.data[index];
                    var option = new Option(obj.widget_name,obj.widget_id);
                    select.append($(option));
                }
                $('button#bsAddWidget').show();
                $('p#bsAddWidgetInfo').show();
            }
            else
            {
                $('p#bsNoWidgets').show();
            }
        }
    });

    // delegate the click event to the detached button
    $('body').delegate('#bsAddWidget','click',function(e) {
        e.preventDefault();
        $('#modal_dialog .modal-body').html( $('div#bs_available_widgets').html() );
        $('#modal_dialog').modal('show');
        $('button.btn-primary').unbind('click').on('click',function(e) {
            var id = $('#modal_dialog select#dashboard_add_widget option:selected').val();
            $('#modal_dialog').modal('hide');
            $.ajax({
                type    : 'POST',
                url     : CAT_ADMIN_URL + '/dashboard/add/'+dashboard_id,
                data    : {
                    widget_id: id
                },
                dataType: 'json',
                success: function(data, status) {
                    // activate for debugging:
                    // console.log(data);
                    if(data.success) {
                        location.reload();
                    } else {
                        $('div.infopanel span#message').html(data.message);
                        $('div.infopanel').addClass('alert alert-danger').show();
                    }
                }
            });
            e.preventDefault();
        });
    });

    // drag & drop
    $(".bs_sortable").sortable({
        connectWith: ".bs_sortable",
        placeholder: "bs_placeholder",
        forcePlaceholderSize: true,
        forceHelperSize: true,
        over:function(event,ui){
            $('.bs_placeholder').parent().addClass('bs_highlight');
        },
        out:function(event,ui){
            $('.bs_placeholder').parent().removeClass('bs_highlight');
        },
        update:function(event,ui){
            // make sure this only fires once
            if (this === ui.item.parent()[0]) {
                $(this).removeClass('bs_highlight');
                $(this).find('.panel').effect("highlight","slow");
                $.ajax({
                    type    : 'POST',
                    url     : CAT_ADMIN_URL + '/dashboard/order',
                    data    : {
                        col      : $(this).data('col'),
                        row      : ui.item.index()+1,
                        id       : ui.item.find('.panel').data('id'),
                        dashboard: dashboard_id
                    },
                    dataType: 'json'
                });
            }
        }
    }).disableSelection();

    // toggle panel
    $(document).on('click', '.panel-heading span.toggle', function(e){
        var $this = $(this);
        if(!$this.hasClass('panel-collapsed')) {
            $this.parents('.panel').find('.panel-body').slideUp();
            $this.addClass('panel-collapsed');
            $this.removeClass('fa-eye').addClass('fa-eye-slash');
            $.ajax({
                type    : 'POST',
                url     : CAT_ADMIN_URL + '/dashboard/toggle',
                data    : {
                    id : $(this).parent().parent().parent().data('id'),
                    vis: 'N',
                    dashboard: dashboard_id
                },
                dataType: 'json'
            });
        } else {
            $this.parents('.panel').find('.panel-body').slideDown();
            $this.removeClass('panel-collapsed');
            $this.removeClass('fa-eye-slash').addClass('fa-eye');
            $.ajax({
                type    : 'POST',
                url     : CAT_ADMIN_URL + '/dashboard/toggle',
                data    : {
                    id : $(this).parent().parent().parent().data('id'),
                    vis: 'Y',
                    dashboard: dashboard_id
                },
                dataType: 'json'
            });
        }
    });

    // remove panel
    $(document).on('click', '.panel-heading span.remove', function(e){
        var $this = $(this);
        $('#modal_remove').modal('show');
        $('#modal_remove button.btn-primary').unbind('click').on('click',function(e) {
            $('#modal_remove').modal('hide');
            var id = $this.data('id');
            $.ajax({
                type    : 'POST',
                url     : CAT_ADMIN_URL + '/dashboard/remove/'+ dashboard_id,
                data    : {
                    widget_id: id
                },
                dataType: 'json',
                success: function(data, status) {
                    // activate for debugging:
                    //console.log(data);
                    if(data.success) {
                        $this.parent().parent().parent().parent().remove();
                    } else {
                        $('div.infopanel span#message').html(data.message);
                        $('div.infopanel').addClass('alert alert-danger').show();
                    }
                }
            });
        });
    });
});