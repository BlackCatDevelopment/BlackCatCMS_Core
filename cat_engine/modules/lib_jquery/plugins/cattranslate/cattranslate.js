if ( typeof jQuery == 'undefined' ) {
  alert( 'FATAL ERROR! jQuery not available!' );
}
else
{
  var url = CAT_URL + '/modules/lib_jquery/plugins/cattranslate/cattranslate.php';
  var translated;
  function cattranslate(string,elem,attributes,module)
  {
    translated = '';
    $.ajax({
      type:    'post',
      url:     url,
      data:    {
        msg:  string,
        attr: attributes,
        mod: module,
        _cat_ajax: 1
      },
      cache:   false,
      async:   false,
      success: function( data ) {
        if ( typeof elem != 'undefined' && typeof elem != '' && elem != '' ) {
          jQuery(elem).text(jQuery(data).text());
        }
        else
        {
          translated = jQuery(data).text();
        }
      }
    });
    if(translated=='') translated = string;
    return translated;
  }
}