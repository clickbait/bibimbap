(function( $ ) {

  
  $.woof_html = {
    
    KEY_DEL: 8,
    KEY_ALT: 18,
    KEY_CMD: 224,
    KEY_ENTER: 13,
    KEY_SHIFT: 16,
    KEY_TAB: 9,
    KEY_RIGHT: 39,
    KEY_UP: 38,
    KEY_DOWN: 40,
    KEY_LEFT: 37,
  
    checked_values: function($inputs) {
      return $inputs.filter(":checked").map(function() {
      return $(this).val();
      }).get(); 
    },
    
    select_set_options: function($select, option_values, detect_groups) {
      
      if (option_values && option_values.length) {
          
          var val = $select.val();
          $select.html(""); // empty the options

          var $parent = $select;
            
          $.each( option_values, function(index, option) {

            var group_matches;
            
            if (detect_groups && option) {
              var text = option.text || option.value;
              
              group_matches = $.trim(text).match(/^\-\-(.*)?/);
            }
            
            if (group_matches && group_matches.length > 1) {
              
              var $optgroup = $('<optgroup>').attr("label", group_matches[1].replace(/--$/, ""));

              $parent = $optgroup;
              
              $select.append($optgroup);
              
            } else {
          
              var $option = $('<option />').attr("value", option.value).html( option.text );

            
              if ($.isArray(val)) {
                
                if ($.inArray(option.value, val) != -1) {
                  $option.attr("selected", "selected");
                }
                
              } else {
                
                if (val == option.value) {
                  $option.attr("selected", "selected");
                }
              
              }
              
              $parent.append( $option );
            
            }
          
          });

        }
    },
    
    input_checkbox_group: function( name, id, items, vals, wrap ) {
      
      var $ret = $([]);
      
      if (!$.isArray(vals)) {
        vals = vals.split(",");
      }
      
      $.each( items, function( index, item ) {

        var value = item.value;
        var label = item.text;
        
        
        
        var id_suffix = "-" + value.toString().dasherize();
      
        var attr = { "id" : id + id_suffix, "class" : "checkbox", "name" : name, "value" : value };
      
        if ($.inArray(value, vals) != -1) {
          attr.checked = "checked";
        }
      
        var $input = $('<input type="checkbox" />').attr(attr);
        var $label = $('<label>').attr({ "for" : id + id_suffix, "class" : "checkbox" }).html(label);

        if (wrap && wrap != "") {
          
          var $wrap = $(wrap);
          $wrap.append($input);
          $wrap.append($label);
          
          $ret = $ret.add( $wrap );
          
        } else {

          $ret = $ret.add( $input );
          $ret = $ret.add( $label );
          
        }

      });
      
      return $ret;
    
    },

    input_radio_group: function( name, id, items, val, wrap ) {
      
      var $ret = $([]);
      
      $.each( items, function( index, item ) {

        var value = item.value;
        var label = item.text;
        
        var id_suffix = "-" + value.toString().dasherize();
      
        var attr = { "id" : id + id_suffix, "class" : "checkbox", "name" : name, "value" : value };
      
        if ($.trim(value) == $.trim(val)) {
          attr.checked = "checked";
        }
      
        var $input = $('<input type="radio" />').attr(attr);
        var $label = $('<label>').attr({ "for" : id + id_suffix, "class" : "checkbox" }).html(label);

        if (wrap && wrap != "") {
          
          var $wrap = $(wrap);
          $wrap.append($input);
          $wrap.append($label);
          
          $ret = $ret.add( $wrap );
          
        } else {

          $ret = $ret.add( $input );
          $ret = $ret.add( $label );
          
        }

      });
      
      return $ret;
    
    },
    
    option_values: function(str, empty_label) {
    
      var values = $.trim(str).split(/\n/);
    
      var select_options = [];

      if (empty_label && empty_label != "") {
        select_options.push({ text: empty_label, value: "" });
      }
    
      for (var i=0; i< values.length; i++) {
        var value = values[i]; 
      
        var matches = value.match(/(.*)\s*\=\s*(.*)/);
      
        if (matches && matches.length == 3) {
          select_options.push( { text: matches[1], value: matches[2] } );
        } else {
          select_options.push( { text: value, value: value } );
        }
      }

      return select_options;

    }
  
    
  };

  $.wh = $.woof_html;

  
  $.wh.ARROW_KEYS = [ $.wh.KEY_RIGHT, $.wh.KEY_UP, $.wh.KEY_DOWN, $.wh.KEY_LEFT ];
  
})(jQuery);