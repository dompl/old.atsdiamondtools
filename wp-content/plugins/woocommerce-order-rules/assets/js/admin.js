jQuery('document').ready(function($) {

   $('.no_row_colour_assigned').tipTip({
                'attribute': 'data-tip',
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            });
  if($('#wc_order_rules_form').length > 0){

    $('#enable_row_colours').change(function(event) {
      if( $(this).is(':checked') ){
        $('#row_colours').show();        
      }else{
        $('#row_colours').hide();
      }
    });

      $('table.orderrules').sortable({
        items: 'tbody tr:not(.inline-edit-row)',
        cursor:'move',
        axis:'y',
        scrollSensitivity:40,
        forcePlaceholderSize: true,
        helper: 'clone',
        opacity: 0.65,
        placeholder: 'wc-metabox-sortable-placeholder',
        start: function(event, ui) {
          if ( ! ui.item.hasClass('alternate') ) ui.item.css( 'background-color', '#ffffff' );
          ui.item.children('td,th').css('border-bottom-width','0');
          ui.item.css( 'outline', '1px solid #aaa' );
        },
        stop: function(event, ui) {
          ui.item.removeAttr('style');
          ui.item.children('td,th').css('border-bottom-width','1px');
        },
        update: function(event, ui) {
          var termid = ui.item.find('.check-column input').val(); // this post id
          //var termparent = ui.item.find('.parent').html();  // post parent

          var prevtermid = ui.item.prev().find('.check-column input').val();
          var nexttermid = ui.item.next().find('.check-column input').val();

          // show spinner
          ui.item.find('.check-column input').hide().after('<img alt="processing" src="images/wpspin_light.gif" class="waiting" style="margin-left: 6px;" />');

          // go do the sorting stuff via ajax
          $.post( ajaxurl, { action: 'wc_order_rules_ordering', id: termid, nextid: nexttermid}, function(response){
              ui.item.find('.check-column input').show().siblings('img').remove();
          });

          // fix cell colors
          $( 'table.orderrules tbody tr' ).each(function(){
            var i = jQuery('table.widefat tbody tr').index(this);
            if ( i%2 == 0 ) jQuery(this).addClass('alternate');
            else jQuery(this).removeClass('alternate');
          });
        }
    });


    if($('.conditions_table #conditions tr').length > 1){
        $('#match_rules_wrap').show();
      }
    var tmp_row = $('table.conditions_table tbody#conditions tr').first().clone();
    $("#row_colour").wpColorPicker();
    ruleConditions($('table.conditions_table tbody#conditions tr').first().find('td.order_var select'));
    $('.remove_conditions').click(function () {
      var rows = $('#wc_order_rules_form table.conditions_table tbody .check-column input:checked');
      if(rows.length > 0){
        var removed_rows = $('#removed_rows').val();
        rows.each(function () {
          var val_c = $(this).val();
          if(val_c.search("new_") != -1){
            $(this).parents('tr:not(.form-field)').remove();
          }else{
            if (removed_rows != '') removed_rows += ',';
            removed_rows += $(this).val();
            console.log(removed_rows);
            $('#removed_rows').val(removed_rows);
            $(this).parents('tr:not(.form-field)').remove();
          }
        });
      }
      if($('.conditions_table #conditions tr').length < 2){
        $('#match_rules_wrap').hide();
      }
      return false;
    });
    $('#wc_order_rules_form').submit(function(){
      $('#wc_order_rules_form .form-invalid').removeClass('form-invalid');
      if($('#rule_name').val() == ''){
        $('#rule_name').parents('.form-field').addClass('form-invalid');
      }
      $('.conditions_table tbody tr').each(function(){
        var tr = $(this);
        tr.find('select:not(:disabled), input:not(:disabled)').each(function(){
        if( $().select2 ){
          if($(this).val() == '' && $(this).parents('.select2-container').length == 0){
            tr.addClass('form-invalid');
          }
        }else{
          if($(this).val() == '' && $(this).parents('.chosen-container').length == 0){
            tr.addClass('form-invalid');
          }          
        }
        });
      });
      if($('#wc_order_rules_form .form-invalid').length > 0 ) return false;
    });
    $('#insert_condition_row').click(function () {
      
      var data_index =  0;
      var new_rows = $('table.conditions_table tbody#conditions tr[data-index^="new_"]');      
      new_rows.each(function(){
        var index = $(this).attr('data-index');
        index = index.substr(4);
        if(index > data_index) data_index = index;
      });
      data_index++;
      data_index = 'new_'+data_index;

      var $new_row = tmp_row;

      var new_row_str = $new_row.prop('outerHTML');
      var old_data_index = $new_row.attr('data-index');

      var $order_variable = $new_row.find('.order_var select');
      var order_variable_str = $( $order_variable.prop('outerHTML') );
          order_variable_str.attr('name', 'rules['+data_index+'][order_var]');
          order_variable_str = order_variable_str.prop('outerHTML');

      var v_html = order_variable_str.replace('selected="selected"', '');

      new_row_str = new_row_str.replace('data-index="'+old_data_index+'"', 'class="new_row" data-index="'+data_index+'"');


      new_row_str = new_row_str.replace('value="'+old_data_index+'"', 'value="'+data_index+'"');

      var order_var = 'order_status';
      var $first_col = $('#default_f .'+order_var+'_inp_first');
      var $second_col = $('#default_f .'+order_var+'_inp_second');
      var f_html = '';
      var s_html = '';
      $first_col.each(function(){
        var str = $(this).prop('outerHTML');
        f_html += str.replace('__replace__', data_index);
      });
      $second_col.each(function(){
        var str = $(this).prop('outerHTML');
        s_html += str.replace('__replace__', data_index);
      });

      $('table.conditions_table tbody#conditions').append(new_row_str);
      $('table.conditions_table tbody#conditions .new_row .order_var').html(v_html);
      $('table.conditions_table tbody#conditions .new_row .order_rule').html(f_html).find('select').show();

      $('table.conditions_table tbody#conditions .new_row .order_value').html(s_html);

      ruleConditions($('table.conditions_table tbody#conditions tr').last().find('td.order_var select'));
      if( $().select2 ) {
        $('tr.new_row select:visible').select2();
      }else{
        $('tr.new_row select:visible').chosen();
      }
      
      $('tr.new_row .order_rule select').change();
      $('tr.new_row input.ui_date').datepicker();
      $('tr.new_row').removeAttr('class');

      if($('.conditions_table #conditions tr').length > 1){
        $('#match_rules_wrap').show();
      }
      return false;
    });

    $('body').on('change', '.order_var select', function(){
      var el = $(this);
      var order_var = el.val();
      var data_index = el.parents('tr:not(.form-field)').attr('data-index');
      var $first_col = $('#default_f .'+order_var+'_inp_first');
      var $second_col = $('#default_f .'+order_var+'_inp_second');
      var f_html = '';
      var s_html = '';
      $first_col.each(function(){
        var str = $(this).prop('outerHTML');
        f_html += str.replace('__replace__', data_index);
      });
      $second_col.each(function(){
        var str = $(this).prop('outerHTML');
        s_html += str.replace('__replace__', data_index);
      });
      el.parents('tr:not(.form-field)').addClass('new_row');
      el.parents('tr:not(.form-field)').find('.order_rule').html(f_html).find('select').show();
      el.parents('tr:not(.form-field)').find('.order_value').html(s_html);
      
      ruleConditions($(this));
      if( $().select2 ) {
        $('tr.new_row select:visible').select2();
      }else{
        $('tr.new_row select:visible').chosen();
      }
      
      $('tr.new_row input.ui_date').datepicker();
      $('tr.new_row .order_rule select').change();
      $('tr.new_row').removeAttr('class');
    });


    $('body').on('change', '.order_rule select', function(){
      var el  = $(this).parents('tr:not(.form-field)').find('.order_var').find('select');
      if( $().select2 ) {
        $(this).parents('tr:not(.form-field)').find('.order_value select.enhanced').select2('destroy'); 
        $(this).parents('tr:not(.form-field)').find(".order_value select.enhanced").removeClass("enhanced").css('display', 'inline').data('select2', null);
        ruleConditions(el);
        $(this).parents('tr:not(.form-field)').find('.order_value select:visible').addClass('enhanced').select2().change();
      }else{
        $(this).parents('tr:not(.form-field)').find('.order_value .chosen-container').remove();
        $(this).parents('tr:not(.form-field)').find(".order_value select").removeClass("chzn-done").css('display', 'inline').data('chosen', null);
        $(this).parents('tr:not(.form-field)').find(".order_value *[class*=chzn]").remove();
        ruleConditions(el);
        $(this).parents('tr:not(.form-field)').find('.order_value select:visible').chosen().change();
      }
    });
   
  }
  if($('table.conditions_table').length > 0){
    $('table.conditions_table tbody#conditions tr').each(function(){
      ruleConditions($(this).find('td.order_var select'));  
    });
    if( $().select2 ) {
      $('table.conditions_table select:visible').select2();
    }else{
      $('table.conditions_table select:visible').chosen();
    }
    $('table.conditions_table .order_rule select').change();
  }

  function ruleConditions(el){
    var o_var  = el.val();
    var o_rule = el.parents('tr:not(.form-field)').find('.order_rule').find('select').val();
    var o_val = el.parents('tr:not(.form-field)').find('.order_value');
    if(o_var == 'order_status'){
      if(o_rule == 'is' || o_rule == 'is not'){
        console.log(o_rule);
        o_val.find('input').hide().attr('disabled', 'disabled');
        o_val.find('select').show().removeAttr('disabled');
      }else{
        o_val.find('input').show().removeAttr('disabled');
        o_val.find('select').hide().attr('disabled', 'disabled');
      }
    }else if( o_var == 'order_date'){
      if(o_rule == 'is' || o_rule == 'is not' || o_rule == 'is after' || o_rule == 'is before'){
        o_val.find('.__chosen_select, input[type="number"]').hide().attr('disabled', 'disabled');
        o_val.find('input.ui_date').show().removeAttr('disabled');
      }else{
        o_val.find('.__chosen_select, input[type="number"]').css('width', '48%').show().removeAttr('disabled');
        o_val.find('input.ui_date').hide().attr('disabled', 'disabled');
      }
    }
  }
});