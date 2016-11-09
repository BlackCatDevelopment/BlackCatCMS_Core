{include file="fuelux_repeater.tpl"
         repeater_id="media"
         repeater_title="Media"
         repeater_header_right="true"
         repeater_filter_menu="true"
         repeater_search="true"
         repeater_filter_menu=array('All types','Images','Audio files','Videos')
}
{include file="backend_modal.tpl"
         modal_id="itemDetails"
         modal_title="Details for media file: "
}

<div id="bsmediafiledetails" class="hidden">
  <table>
    <thead>
      <tr>
        
      </tr>
    </thead>
  </table>
</div>

<script type="text/javascript">
    $(function() {
        // define the columns in your datasource
		var columns = [
            {
				label: '',
				property: 'preview',
				sortable: false
			},
			{
				label: cattranslate('Name'),
				property: 'filename',
				sortable: true
			},
			{
				label: cattranslate('Size'),
				property: 'hfilesize',
				sortable: true
			},
            {
				label: cattranslate('Date'),
				property: 'moddate',
				sortable: true
			},
            {
                label: cattranslate('Type'),
                property: 'mime_type',
                sortable: true
            }
        ];

        var txt_preview = cattranslate('Preview');

        function customColumnRenderer(helpers, callback) {
			// determine what column is being rendered
			var column = helpers.columnAttr;
			// get all the data for the entire row
			var rowData = helpers.rowData;
			var customMarkup = '';
			// only override the output for specific columns.
			// will default to output the text value of the row item
			switch(column) {
				case 'preview':
					if(typeof rowData.image != 'undefined')
                    {
                        customMarkup = '<img src="'+rowData.preview+'" alt="'+txt_preview+'" title="'+txt_preview+'" class="thumb bs-media-details" />';
                    }
					break;
				default:
					// otherwise, just use the existing text value
					customMarkup = helpers.item.text();
					break;
			}
			helpers.item.html(customMarkup);
			callback();
        }

        $('#media').repeater({
            list_columnRendered: customColumnRenderer,
            {literal}
            thumbnail_template: '<div class="thumbnail repeater-thumbnail"><img height="75" src="{{preview}}" width="65"><span>{{filename}}</span></div>',
            {/literal}
            dataSource: function (options, callback) {
                var filter = '';
                if(typeof(options.filter) == 'object') {
                    switch(options.filter.value) {
                        case 'Images':
                            filter = 'image/*';
                            break;
                        case 'Audio files':
                            filter = 'audio/*';
                            break;
                        case 'Videos':
                            filter = 'video/*';
                            break;
                        default:
                            filter = '';
                            break;
                    }
                }
                $.ajax({
					type: 'post',
					url: '{$CAT_ADMIN_URL}/media/list',
					dataType: 'json',
                    data: {
                        filter: filter
                    }
				})
                .done(function(data) {
                    var dataSource = {
						page: 0,
						pages: 1,
						count: data.length,
						start: 0,
						end: data.length,
						columns: columns,
						items: data
					};

					// invoke callback to render repeater
                    callback(dataSource);
                });
            }
        });
        $('div.repeater').show();

        $('body').delegate('.bs-media-details','click',function(e) {
            var div = $('div#bsmediafiledetails').clone().detach();
            $('#itemDetails .modal-title span').remove();
            $('#itemDetails .modal-title').html($('#itemDetails .modal-title').text() + ' <span>' + $(this).attr('src') + '</span>');
            $(div).appendTo('.modal-body').removeClass('hidden');
            $('#itemDetails').modal('show');
        });
    });
</script>