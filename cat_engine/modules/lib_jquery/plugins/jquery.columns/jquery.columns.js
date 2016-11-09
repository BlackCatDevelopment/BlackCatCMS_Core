// *****************************************************************************
// This plugin was inspired by
// https://github.com/fzondlo/jquery-columns
//
// (c) 2016 BlackBird Webprogrammierung
// *****************************************************************************

(function($){
    jQuery.fn.columnize = function(num_columns,options) {

        // init vars
        var _this    = $(this),
            new_html = '',
            defaults = {
                class_prefix: 'col',
                ul_classes: null
            };

        // merge passed options with defaults
        var o = $.extend(true, {}, defaults, options || {});

        // Turns an array list of UL elements into html
        function createHTML(list) {
            if(typeof list == 'undefined') return;
            html = '';
            for (var i = 0; i < list.length; i++) {
                html += '<li>' + $(list[i]).html() + '</li>';
            };
            return html;
        }

        // sort by column
        var items = $(this).find('li').sort(function (a, b) {
            var colA = $(a).data('col');
            var colB = $(b).data('col');
            // sort by first level
            var result = (colA < colB ? -1 : (colA > colB ? +1 : 0));
            if(!result) { // level 1 is equal, sort by level 2
                var rowA = $(a).data('row');
                var rowB = $(b).data('row');
                result = (rowA < rowB ? -1 : (rowA > rowB ? +1 : 0));
            }
            return result;
        });

        // create columns
        for(var n=1; n<=num_columns; n++) {
            // get items for this column
            var col_items = $.grep(items,function(item) {
                return $(item).data('col') === n;
            });
            // for debugging
            //console.log('column',n,'items',col_items);
            new_html = new_html +
                       '<ul data-col="' + n + '" class="' + o.ul_classes + ' ' + o.class_prefix + n + '" style="width:'+Math.floor(100/num_columns)+'%">' +
                       createHTML(col_items) +
                       '</ul>';
        }

        $(this).replaceWith(new_html);

    };
})(jQuery);
