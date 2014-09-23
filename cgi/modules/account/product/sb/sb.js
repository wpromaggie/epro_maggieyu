var MAX_TITLE = 25;
var MAX_BODY_TEXT = 90;

$(document).ready(function(){

    $('#os_tbody tr').click(function(){
        var oid = $(this).attr("oid");
        $('#order_id').val(oid);
        f_act('/sbs/sb/order_details/');
    });

    $('#cs_tbody tr').click(function(){
        var cid = $(this).attr("cid");
        $('#client_id').val(cid);
        f_act('/sbs/sb/client_info/');
    });

    $('#gs_tbody tr').click(function(){
        var gid = $(this).attr("gid");
        $('#client_id').val(cid);
        f_act('/sbs/sb/client_info/');
    });

    $('#view_billing').click(function(){
        f_act('/sbs/sb/billing_info/');
    });

    $('a#likepage_details').click(function(){
        f_act('/sbs/sb/likepage_details/');
        return false;
    });

    $('#edit_account').click(function(){
        f_act('/sbs/sb/edit_client_info/');
    });

    $('.nogo').click(function(){
        return false;
    });

    $('#order_filter').change(function(){
        $("#f").submit();
    });

    $('#order_edit_filter').change(function(){
        $("#f").submit();
    });

    $('#client_search_go').click(function(){
        $("#f").submit();
    });

    $('#groups').change(function(){
        $("[name='oid']").val($(this).val());
        $("#f").submit();
    });

    $('#delete_account').click(function(){
        return confirm('Are you sure you want to delete this account?');  
    });

    $('#destroy_account').click(function(){
        return confirm('Are you sure you want to destroy this account? Please make sure all campaigns are removed/paused in facebook!');
    });

    if($('#billing_info')){
        $('#cc_select').change(function(){
           if($(this).val()!=""){
                f_act('/sbs/sb/edit_cc/');
           }
        });
    }

/*
    if($('#due_payments').length){

        $('.client_link').click(function(){
            var cid = $(this).attr("cid");
            $('#client_id').val(cid);
            f_act('/sbs/sb/client_info/');
            return false;
        });

        $('#submit_payments').click(function(){
           
            $('.payment').each(function(){
                if($(this).val()!=""){
                    var request = {
                      'row_id': $(this).closest('tr').attr('id'),
                      'amount': $(this).val(),
                      'cid': $(this).attr('cid'),
                      'gid': $(this).attr('gid')
                    };
                    ajax_post('ajax_submit_payment', ajax_submit_payment_callback, request);
                }
            });
            
            return false;
        })

    }
    */

    if($('#order_details').length){

        $('#order_details').find('#run_status_select').change(function(){
           e2.action_submit('update_grp_run_status');
        });
        
        $('#edit_group_url').click(function(){
            $('#group_url_display').hide();
            $('#edit_url_block').show();
        });
        $('#edit_url_block .cancel').click(function(){
            $('#group_url_display').show();
            $('#edit_url_block').hide();
        });

        $('#edit_likepage_url').click(function(){
            $('#likepage_url_display').hide();
            $('#edit_likepage_url_block').show();
        });
        $('#edit_likepage_url_block .cancel').click(function(){
            $('#likepage_url_display').show();
            $('#edit_likepage_url_block').hide();
        });

        $(".approve_ad").click(function(){
           var ad_id = $(this).closest(".ad_block").attr("ad_id");
           $('#selected_ad').val(ad_id);
           e2.action_submit('approve_ad');
           return false;
        });


    }

    if($('#edit_ad').length){
        $('#run_status_select').change(function(){
           e2.action_submit('update_ad_run_status');
        });
    }
    

    if($('#export').length){
        $('#export_many').click(function(){
            f_act('/sbs/sb/export_excel/');
            return flase;
        });
    }



    if($('#display_ads').length){

        $(".creative_sample_container").click(function(){
            var ad_id = $(this).closest(".ad_block").attr("ad_id");
            //alert(ad_id);
            $('#selected_ad').val(ad_id);
            $('#f').attr('action', e2.url('/account/product/sb/edit_ad/')+window.location.search);
            $("#f").submit();
        });

    }

    if($('#ad_details').length){

        $('#edit_ad_name').click(function(){
            $('#ad_name_display').hide();
            $('#edit_ad_name_block').show();
        });
        $('#edit_ad_name_block .cancel').click(function(){
            $('#ad_name_display').show();
            $('#edit_ad_name_block').hide();
        });

        $('#edit_group_name').click(function(){
            $('#group_name_display').hide();
            $('#edit_group_name_block').show();
        });
        $('#edit_group_name_block .cancel').click(function(){
            $('#group_name_display').show();
            $('#edit_group_name_block').hide();
        });

        $('#edit_group_oid').click(function(){
            $('#group_oid_display').hide();
            $('#edit_group_oid_block').show();
        });
        $('#edit_group_oid_block .cancel').click(function(){
            $('#group_oid_display').show();
            $('#edit_group_oid_block').hide();
        });

        $('#edit_daily_budget').click(function(){
            $('#daily_budget_display').hide();
            $('#edit_daily_budget_block').show();
        });
        $('#edit_daily_budget_block .cancel').click(function(){
            $('#daily_budget_display').show();
            $('#edit_daily_budget_block').hide();
        });

    }

    if($('#edit_nav').length){
        $('#edit_nav li').click(function(){
            var target = $(this).attr('target');
            $('#edit_type').val(target);
            $('#f').attr('action', e2.url('/account/product/sb/edit_ad/')+window.location.search);
            $("#f").submit();
        })
    }

    if($('#edit_ad_text').length){

        update_ad_fields();

        $('#title').keyup(function(){
            $('.creative_sample_container').find('.creative_sample_title').text($(this).val());
            update_ad_fields();
        });

        $('#body_text').keyup(function(){
            $('.creative_sample_container').find('.creative_sample_body_text').text($(this).val());
            update_ad_fields();
        });

        $('input[ticket_id]').click(function(){ $('#ticket_id').val($(this).attr('ticket_id')); });
    }

    if($('#edit_ad_image').length){

        $("#upload").change(function(){
            e2.action_submit('upload_image');
        });

        $('#remove_image').click(function(){
            $('.ad_block').find('.creative_sample_image').text("");
            $('.ad_block').find('.image_file').val('');
            $(this).hide();
            return false;
        });

        $(".remove_container").hover(
            function(){
                $(this).find('.remove').show();
            },
            function(){
                $(this).find('.remove').hide();
            }
         );
         $('.remove').click(function(){
             var remove_img = confirm("Are you sure you want to remove this image?");
             if(remove_img){
                 var file_name = $(this).closest('.image_frame').find('img').attr('src').split('/').pop();
                 $('#destroy_image').val(file_name);
                 e2.action_submit('destroy_image');
             }
         });

        $(".draggable").draggable({
            helper: 'clone',
            opacity: 0.5,
            cursor: 'pointer',
            zIndex: 2700
        });

        $(".draggable").dblclick(function(){
            var image = $(this).find('img').clone();
            $(".droppable").find('.creative_sample_image').html(image);

            var file_name = $(this).find('img').attr('src').split('/').pop();
            $('#image_file').val(file_name);
        });

        $(".droppable").droppable({
                drop: function(event, ui){
                    $(this).removeClass('drophover');

                    // clone the image
                    var image = ui.draggable.find('img').clone();
                    $(this).find('.creative_sample_image').html(image);

                    // get image filename
                    var file_name = ui.draggable.find('img').attr('src').split('/').pop();
                    $('#image_file').val(file_name);

                    // show the remove text
                   $('#remove_image').show();
                },
                tolerance: 'pointer',
                over: function(event, ui) {
                    $(this).addClass('drophover');
                },
                out: function(event, ui) {
                    $(this).removeClass('drophover');
                }
        });


    }

    if($('#edit_ad_keywords').length){
        $('.add_keyword').click(function(){
            
            var target = $('#edit_ad_keywords ul');
            var num = $('#edit_ad_keywords li').length;

            var input = '<li><label>Keyword: '+(num+1)+' </label>';
            input += '<input type="hidden" value="" name="ad_keywords['+num+'][id]">';
            input += '<input type="text" value="" name="ad_keywords['+num+'][text]">';
            input += '</li>';

            target.append(input);

            return false;
        });
    }

    if($('#edit_ad_target_users').length){

        if($('#country').val()=='US'){
            $('#location_type_selection').show();
            set_location();
        }

        $('#country').change(function(){

            var country = $(this).val();

            if(country=='US'){
                $('#location_type_selection').show();
            } else {
                $('#location_type_selection').hide(); 
            }

            $('#location_type_selection input').each(function(){
               if($(this).val()=='country'){
                   $(this).attr('checked','checked');
               }
            });

            set_location();

        });

        $('.state').change(function(){
            disable_selected_states()
        });

        $('#location_type_selection input:radio').change(function(){
            set_location();
       });

       $('#add_state').click(function(){
           var state_block = $('.state_block:first').clone(true);
           var state_num = $('.state_block').length;
           $(state_block).find('.state_id').remove();
           $(state_block).find('.state').attr('name','ad_state['+state_num+'][state]');
           $(state_block).find(':selected').removeAttr('selected');
           $(state_block).find('.remove_state').show();
           $('#more_states').append(state_block);
           disable_selected_states();
       });

       $('.remove_state').click(function (){
          var state_block = $(this).closest('.state_block')
          $(state_block).hide();
          $(state_block).find('.state').val('');
          disable_selected_states();
       });

       $('#add_city').click(function(){
           var city_block = $('.city_block:first').clone(true);
           var city_num = $('.city_block').length;
           $(city_block).find('.city_id').remove();
           $(city_block).find('.city').attr('name','ad_city['+city_num+'][city]').val('');
           $(city_block).find('.city-state').attr('name','ad_city['+city_num+'][state]').val('');
           $(city_block).find('.remove_city').show();
           $('#more_cities').append(city_block);
       });

       $('.remove_city').click(function (){
          var city_block = $(this).closest('.city_block')
          $(city_block).hide();
          $(city_block).find('.city').val('');
       });

       $('#has_radius').click(function () {
            if($("#has_radius:checked").length){
                $('#radius').removeAttr("disabled");
            } else {
                $("#radius").attr("disabled","disabled");
                $('#radius').val('');
            }
       });

       // demographic stuff
       $('#min_age').change( function(){
          if($(this).val()>$('#max_age').val()){
              if($('#max_age').val()!=''){
                $('#max_age').val($(this).val());
              }
          }
       });
       $('#max_age').change( function(){
          if($(this).val()<$('#min_age').val() && $(this).val()!=''){
              if($('#max_age').val()!=''){
                $('#min_age').val($(this).val());
              }
          }
       });

       if($('.relationship_status:checked').length<1){
           $('.relationship_status[value=""]').attr('checked', 'checked');
       }

       $('.relationship_status').click(function (){
           if($(this).val()==''){
               $('.relationship_status').each(function (){
                  $(this).removeAttr('checked');
               });
               $(this).attr('checked', 'checked');
           } else {
               if($('.relationship_status:checked').length<1){
                   $('.relationship_status[value=""]').attr('checked', 'checked');
               } else {
                   $('.relationship_status[value=""]').removeAttr('checked');
               }
           }
       });


       // education stuff
       set_education();
       $('.remove_company:first').hide();

       $('#education_type_selection input:radio').change(function(){
            set_education();
       });

       $('#add_college').click(function(){
           var college_block = $('.college_block:first').clone(true);
           var college_num = $('.college_block').length;
           $(college_block).find('.college_id').remove();
           $(college_block).find('.college').attr('name','ad_college['+college_num+'][college]').val('');
           $(college_block).find('.remove_college').show();
           $('#more_colleges').append(college_block);
       });
       $('.remove_college').click(function (){
          var college_block = $(this).closest('.college_block')
          $(college_block).hide();
          $(college_block).find('.college').val('');
       });

       $('#add_major').click(function(){
           var major_block = $('.major_block:first').clone(true);
           var major_num = $('.major_block').length;
           $(major_block).find('.major_id').remove();
           $(major_block).find('.major').attr('name','ad_major['+major_num+'][major]').val('');
           $(major_block).find('.remove_major').show();
           $('#more_majors').append(major_block);
       });
       $('.remove_major').click(function (){
          var major_block = $(this).closest('.major_block')
          $(major_block).hide();
          $(major_block).find('.major').val('');
       });

       $('#college_year_min').change( function(){
           if($(this).val()!=''){
            if($(this).val()>$('#college_year_max').val()){
                $('#college_year_max').val($(this).val());
            }
          } else {
              $('#college_year_max').val('');
          }
       });

        $('#college_year_max').change( function(){
           if($(this).val()!=''){
            if($(this).val()<$('#college_year_min').val() || $('#college_year_min').val('')){
                $('#college_year_min').val($(this).val());
            }
          } else {
              $('#college_year_min').val('');
          }
       });


       $('#add_company').click(function(){
           var company_block = $('.company_block:first').clone(true);
           var company_num = $('.company_block').length;
           $(company_block).find('.company_id').remove();
           $(company_block).find('.company').attr('name','ad_company['+company_num+'][company]').val('');
           $(company_block).find('.remove_company').show();
           $('#more_companies').append(company_block);
       });
       $('.remove_company').click(function (){
          var company_block = $(this).closest('.company_block')
          $(company_block).hide();
          $(company_block).find('.company').val('');
       });

    }

    if($('#edit_ad_bids').length){

        $('#bid_options input:radio').change( function () {
           set_bid_type();
        });

    }

    

});


function update_ad_fields(){
    var title_count = MAX_TITLE - $('#title').val().length;
    $('#title_char_count').text(title_count+' characters left');

    var body_text_count = MAX_BODY_TEXT - $('#body_text').val().length;
    if(body_text_count<0){
        $('#body_text').val( $('#body_text').val().slice(0,MAX_BODY_TEXT) );
        body_text_count = 0;
    }
    $('#body_text_char_count').text(body_text_count+' characters left');

}

function set_bid_type(){
    var bid_type = $("#bid_options input:radio:checked").val();
    var min_span = $('#edit_max_bid label span');

    switch(bid_type){

        case 'cpm':
            $(min_span).text("(min 0.02)");
            
            break;

        case 'cpc':
            $(min_span).text("(min 0.01)");
            
            break;

        default:
            $(min_span).text("(min 0.01)");
            
            break;

    }
}

function set_location(){

    var location_type = $("#location_type_selection input:radio:checked").val();

    switch(location_type){

        case 'country':
            $('#state_select').hide();
            $('#city_select').hide();
            break;

        case 'state':
            disable_selected_states();
            $('.remove_state:first').hide();
            $('#state_select').show();
            $('#city_select').hide();
            break;

        case 'city':
            $('.remove_city:first').hide();
            $('#state_select').hide();
            $('#city_select').show();
            if($('#has_radius:checked').length){
                $('#radius').removeAttr('disabled');
            } else {
                $('#radius').val('');
                $('#radius').attr('disabled', 'disabled');
            }
            break;

       default:
            $('#state_select').hide();
            $('#city_select').hide();
            break;
    }
    
}

function set_education(){

    var education_type = $("#education_type_selection input:radio:checked").val();

    switch(education_type){

        case 'College Grad':
            $('#college_fields').show();
            $('.remove_college:first').hide();
            $('#major_fields').show();
            $('.remove_major:first').hide();
            $('#graduation_years').hide();
            break;

        case 'College':
            $('#college_fields').show();
            $('.remove_college:first').hide();
            $('#major_fields').show();
            $('.remove_major:first').hide();
            $('#graduation_years').show();
            break;

        case 'High School':
            $('#college_fields').hide();
            $('#major_fields').hide();
            $('#graduation_years').hide();
            break;

       default:
            $('#college_fields').hide();
            $('#major_fields').hide();
            $('#graduation_years').hide();
            break;

    }

}

function disable_selected_states(){
    var selected_states = [];

    //get all selected states
    $('.state').each( function (){
        if($(this).val()!=''){
            selected_states.push($(this).val());
        }
    });

    // enabled all states
    $('.state option:disabled').removeAttr('disabled');

    // go over each state select menu and disable states that are already selected
    $('.state').each( function (){
        for(var i in selected_states){
            var state = selected_states[i];
            if($(this).val()!=state){
                $(this).find('option[value="'+state+'"]').attr('disabled','disabled');
            }
        }
    });
}

//
// ajax callback functions
//
function ajax_submit_payment_callback(request, response){
    
    var target_row = $('#'+request.row_id);
    var payment_field = target_row.find('.payment');

    if(response.success){
        //payment was successfull
        if(!payment_field.hasClass('success')){
            payment_field.removeClass('failed');
            payment_field.addClass('success');
        }
        payment_field.val('');
        target_row.find('.last_payment_amount').text(response.payment_amount);
        target_row.find('.last_payment_date').text(response.payment_date);
        target_row.find('.payment_err_msg').empty();
        
    } else {
        //payment failed
        if(!payment_field.hasClass('failed')){
            payment_field.removeClass('success');
            payment_field.addClass('failed');
        }
        target_row.find('.payment_err_msg').text(response.err_msg);
    }

}

function sb_spend()
{
	this.init_cols();
	this.init_data();
	
	$('#spend_wrapper').table({
		cols:this.cols,
		data:this.data,
		show_totals:false
	});
}

sb_spend.prototype.init_cols = function()
{
	this.cols = [
		{key:'date'},
		{key:'imps'},
		{key:'clicks'},
		{key:'cost',format:'dollars'}
	];
};

sb_spend.prototype.init_data = function()
{
	var date_str, i, d, start = Date.str_to_js($('#start_date').val()),
		end_str = $('#end_date').val(),
		end = Date.str_to_js(end_str);
	
	this.data = [];
	for (i = start; ((date_str = Date.js_to_str(i)) <= end_str); i = new Date(i.getTime() + 86400000))
	{
		if (!globals.spend[date_str])
		{
			this.data.push({date:date_str});
		}
		else
		{
			d = globals.spend[date_str];
			d.date = date_str;
			this.data.push(d);
		}
		if (this.data.length > 100) break;
	}
};
