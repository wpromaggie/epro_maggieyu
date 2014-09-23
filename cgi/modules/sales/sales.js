$(document).ready(function(){

	$('#ql_pro_package').change(function(){
		var fee, ctrak;
		switch($(this).val())
		{
			case '1':
				ctrak = '199';
				fee = '395'
				break;
			default:
				ctrak = '';
				fee = '';
				break;
		}
		$('#ql_pro_ct_fee').val(ctrak);
		$('.ql_fee').val(fee);
	});

	$("#gs_pro_package").change(function(){
		var hours, mgmt_fee, setup_fee;
		setup_fee = '495';
		switch($(this).val())
		{
			case '1':
				hours = '15';
				mgmt_fee = '495'
				break;
			case '2':
				hours = '30';
				mgmt_fee = '995'
				break;
			case '3':
				hours = '45';
				mgmt_fee = '1495'
				break;
			default:
				hours = '';
				mgmt_fee = '';
				setup_fee = '';
				break;
		}
		$('#gs_pro_hours').val(hours);
		$('#gs_pro_mgmt_fee').val(mgmt_fee);
		$('#gs_pro_setup_fee').val(setup_fee);
	});

    $("#sort_prospects").change(function(){
        f.submit();
    })

    $("#delete_prospect").click(function(){
        is_confirmed = confirm('Are you sure you want to delete this prospect?');
        if(is_confirmed){
            $f('go','delete_prospect');
            $("#f").submit();
        } else {
            return false;
        }
            return true;
    });

    $("a.slide").click(function(){
        var target = $(this).parent().next(".edit_block");
        if(target.is(":hidden")){
            target.slideDown("fast");
        } else {
            target.slideUp("fast");
        }
        return false;
    });

    $(".edit_block .cancel").click(function(){
       $(this).closest(".edit_block").hide();
       return false;
    });

    $(".edit_block .save").click(function(){
        $(this).closest(".edit_block").find(".default_text").each(function(){
            var request = {
              's1': $(this).attr('s1'),
              's2': $(this).attr('s2'),
              's3': $(this).attr('s3'),
              'list_order': $(this).attr('list_order'),
              'text': $(this).val()
            };
            ajax_post('ajax_save_default_text', ajax_save_default_text_callback, request);
        });
        return false;
    })

    $(".default_text").change(function(){
        $(this).addClass("edited");
    });

    $("#parent_client").change(function(){
        var edit_confirm = confirm("Would you like to use the client information from the selected client?")
        if(edit_confirm){
            $f('go','parent_client_select');
            $("#f").submit();
        }
    });
    
    $("#ppc_budget").change(function(){
		if($("#ppc_mgmt_perc").val!==""){
			var ppc_mgmt = Math.round( $("#ppc_mgmt_perc").val() / 100 * $(this).val() );
			ppc_mgmt = (ppc_mgmt > 1000) ? ppc_mgmt : 1000;
			$("#ppc_mgmt").val(ppc_mgmt);
	    }
    });
    
    $("#ppc_mgmt_perc").keyup(function(){
		if(check_ppc_budget()){
			var ppc_mgmt = Math.round( $(this).val() / 100 * $("#ppc_budget").val() );
			ppc_mgmt = (ppc_mgmt > 1000) ? ppc_mgmt : 1000;
			$("#ppc_mgmt").val(ppc_mgmt);
		}
	});
	
	$("#ppc_mgmt").keyup(function(){
		if(check_ppc_budget()){
			$("#ppc_mgmt_perc").val(Math.round( $(this).val() / $("#ppc_budget").val() * 100 ));
		}
	}).change(function(){
		if(check_ppc_budget()){
			if($(this).val()<1000){
				//remove min ppc_mgmt
				//alert("The min management fee for PPC is $1000. Rookie mistake!");
				//$(this).val('1000');
				//$("#ppc_mgmt_perc").val(Math.round( $(this).val() / $("#ppc_budget").val() * 100 ));
			}
		}
	});
	
	$("#fba_budget").change(function(){
		if($("#fba_mgmt_perc").val!==""){
			var fba_mgmt = Math.round( $("#fba_mgmt_perc").val() / 100 * $(this).val() );
			fba_mgmt = (fba_mgmt > 1000) ? fba_mgmt : 1000;
			$("#fba_mgmt").val(fba_mgmt);
	    }
    });
    
    $("#fba_mgmt_perc").keyup(function(){
		if(check_fba_budget()){
			var fba_mgmt = Math.round( $(this).val() / 100 * $("#fba_budget").val() );
			fba_mgmt = (fba_mgmt > 1000) ? fba_mgmt : 1000;
			$("#fba_mgmt").val(fba_mgmt);
		}
	});
	
	$("#fba_mgmt").keyup(function(){
		if(check_fba_budget()){
			$("#fba_mgmt_perc").val(Math.round( $(this).val() / $("#fba_budget").val() * 100 ));
		}
	}).change(function(){
		if(check_fba_budget()){
			if($(this).val()<1000){
				//alert("The min management fee for FB Ads is $1000.");
				//$(this).val('1000');
				//$("#fba_mgmt_perc").val(Math.round( $(this).val() / $("#fba_budget").val() * 100 ));
			}
		}
	});
    
    $("#infographic").click(function(){
		if($(this).attr("checked")){
			$("#seo_ig_amount").val("3000");
			$("#ig_num").val("1")
		} else {
			$("#ig_num").val("0")
			$("#seo_ig_amount").val("0");
		}
	});
	
	$("#ig_num").change(function(){
		if($(this).val()==0){
			$("#infographic").attr("checked", "");
		} else {
			$("#infographic").attr("checked", "checked");
		}
		$("#seo_ig_amount").val(3000*$("#ig_num").val()); 
	});
	
	$('#account_links a:contains("Create No Charge")').click(function(){
		var form, a;
		
		a = $(this);
		form = $('#f');
		
		form.attr('action', a.attr('href'));
		e2.action_submit('action_create_no_charge');
		return false;
	});
	
	if ($('.client_breakdown').length && globals.payments)
	{
		init_sales_rep_client_breakdowns();
	}
	
	
	if($('#package-info').length){
		
		$('input.date').date_picker();
		
		$('select.package-select').change(function(){
			var $package = $(this).closest('.package');
			var price = 0;
			switch($(this).val()){
				case 'starter':
					price = '149';
					break;
				case 'core':
					price = '249';
					break;
				case 'premier':
					price = '349';
					break;
			}
			
			$package.find('.cost').val(price);
			$package.find('.setup-fee').val(price);
		});
		
		$('input[name=submit]').click(function(){
			var valid = true;
			$('input.required').each(function(){
				if($(this).val()==''){
					valid = false;
					return false;
				}
			});
			if(!valid){
				alert('Missing required field(s)');
				return false;
			}
		});
		
	}
	
	
});

function init_sales_rep_client_breakdowns()
{
	var i, j, payment, time_period, part_types, type, dept, amount, part_amounts, rep_id, dept,
		all_cols = {},
		all_data = {};
	
	for (i = 0; i < globals.payments.length; ++i)
	{
		payment = globals.payments[i];
		rep_id = $('#'+payment.cl_id).closest('[rep_id]').attr('rep_id');
		time_period = e2.get_time_period_from_date('Monthly', payment.date);
		if (rep_id)
		{
			part_types = payment.part_types.split("\t");
			part_amounts = payment.part_amounts.split("\t");
			for (j = 0; j < part_types.length; ++j)
			{
				type = part_types[j];
				amount = Number(part_amounts[j]);
				dept = globals.type_to_dept[type];
				
				accumulate(all_cols, [rep_id, 'depts', dept], 1);
				accumulate(all_data, [rep_id, time_period, dept], amount);
				accumulate(all_data, [rep_id, time_period, 'total'], amount);
			}
		}
	}
	
	show_rep_data(all_cols, all_data);
}

function show_rep_data(all_cols, all_data)
{
	var rep_id, tmp_cols, tmp_data, cols, data, dept, depts_on, time_period, d, k;
	
	$('.client_breakdown').each(function(i, elem){
		elem = $(elem);
		rep_id = elem.attr('rep_id');
		
		tmp_cols = all_cols[rep_id];
		tmp_data = all_data[rep_id];
		
		if (tmp_cols && tmp_data)
		{
			cols = [{key:'month'},{key:'total',format:'dollars'}];
			//for (k in tmp_cols.depts)
			//{
				//cols.push({key:k,format:'dollars'});
			//}
			depts_on = {};
			for (k in globals.type_to_dept)
			{
				dept = globals.type_to_dept[k];
				if (tmp_cols.depts[dept] && !depts_on[dept])
				{
					cols.push({key:dept,format:'dollars'});
					depts_on[dept] = 1;				}
			}
			
			
			data = [];
			for (time_period in tmp_data)
			{
				d = {'month':time_period};
				for (k in tmp_data[time_period])
				{
					d[k] = tmp_data[time_period][k];
				}
				data.push(d);
			}
			
			$('#client_breakdown_'+rep_id).table({
				data:data,
				cols:cols,
				sort_init:'date',
				sort_dir_init:'desc'
			});
		}
	});
}

function check_ppc_budget(){
	if( $("#ppc_budget").val()!="" && $("#ppc_budget").val()!=0 && !isNaN($("#ppc_budget").val()) ) return true;
	alert("Please complete the PPC Budget field first.");
	$("#ppc_mgmt").val(0);
	$("#ppc_mgmt_perc").val(0);
	$("#ppc_budget").focus();
	return false;
}

function check_fba_budget(){
	if( $("#fba_budget").val()!="" && $("#fba_budget").val()!=0 && !isNaN($("#fba_budget").val()) ) return true;
	alert("Please complete the FB Ad Budget field first.");
	$("#fba_mgmt").val(0);
	$("#fba_mgmt_perc").val(0);
	$("#fba_budget").focus();
	return false;
}

function ajax_save_default_text_callback(request, response){
    //alert(response);
    //response = eval("(" + response + ")");
}

function company_handle_key(event)
{
	// if user hasn't already edited url key,
	// sync url key with company value
	if (f.url_key.getAttribute("dirty") == 0)
		f.url_key.value = f.prospect_company.value.toLowerCase().replace(/ /g, '-');
}

// if user manually edits the url key, set to dirty so company field changes no longer sync
function url_key_handle_key(event)
{
	f.url_key.setAttribute("dirty", 1);
}

function ppc_package_handle(event){
	if(f.ppc_package.value == 2){
		f.ppc_setup_fee.value = "";
		f.ppc_setup_fee.disabled = "disabled";
	} else {
		f.ppc_setup_fee.disabled = "";
	}
}

function seo_package_handle(event){
	switch (f.seo_package.value){
		case '0': f.seo_package_amount.value = ''; break;
		case '5': f.seo_package_amount.value = '2000'; break; // Local/Small Business Package
		case '1': f.seo_package_amount.value = '3000'; break;
		case '2': f.seo_package_amount.value = '5000'; break;
		case '3': f.seo_package_amount.value = '7500'; break;
	}
        update_seo_total();
}

function seo_blog_handle(event){
    if(f.seo_blog.checked == ""){
        f.seo_blog_amount.value = "0";
	f.seo_blog_amount.disabled = "disabled";
    } else {
        f.seo_blog_amount.disabled = "";
    }
    update_seo_total();
}

function seo_blog_mgmt_handle(event){
    if(f.seo_blog_mgmt.checked == ""){
        f.seo_blog_mgmt_amount.value = "0";
	f.seo_blog_mgmt_amount.disabled = "disabled";
    } else {
        f.seo_blog_mgmt_amount.disabled = "";
    }
    update_seo_total();
}

function update_seo_total(){
    f.seo_amount.value = parseFloat(f.seo_package_amount.value);// + parseFloat(f.seo_blog_amount.value) + parseFloat(f.seo_blog_mgmt_amount.value);
    f.seo_monthly_amount.value = parseFloat(f.seo_package_amount.value);// + parseFloat(f.seo_blog_mgmt_amount.value);
}

function smo_package_handle(event){
	switch (f.smo_package.value){
		case '0': f.smo_amount.value = ''; break;
		case '1': f.smo_amount.value = '3000'; break;
		case '2': f.smo_amount.value = '5000'; break;
		case '3': f.smo_amount.value = '7500'; break;
	}
}

function slb_package_handle(event){
	switch (f.slb_package.value){
		case '0': f.slb_amount.value = ''; break;
		case '1': f.slb_amount.value = '3000'; break;
		case '2': f.slb_amount.value = '7000'; break;
	}
}

function wd_package_handle(event){
	switch (f.wd_package.value){
		case '0': f.wd_package_amount.value = ''; break;
		case '1': f.wd_package_amount.value = '1995'; break;
		case '2': f.wd_package_amount.value = '3995'; break;
		case '3': f.wd_package_amount.value = '4995'; break;
	}
}

function wd_landing_page_handle(event){
	if(f.wd_landing_page.checked){
		f.wd_landing_page_testing.disabled = "";
	} else {
		f.wd_landing_page_testing.checked="";
		f.wd_landing_page_testing.disabled = "disabled";
	}
}

function prospect_charge_amount_change()
{
	var elem, i, sum;
	
	sum = 0;
	for (i = 0; i < f.elements.length; ++i)
	{
		elem = f.elements[i];
		if (elem && elem.getAttribute && elem.getAttribute("is_amount_part"))
		{
			if (!isNaN(elem.value)) sum += Number(elem.value);
		}
	}
	// check for discount
	if (!$.empty('#discount')) sum -= Number($("#discount").text().replace("$", ""));
	$("#total_amount").html(Format.dollars(sum));
}

function prospect_init()
{
	if (f.prospect_company)
	{
		f.prospect_company.onkeyup = company_handle_key;
		f.url_key.onkeyup = url_key_handle_key;
	}	else if (f.payment_options) {
		checkPayment('payment_options');
	}
	
	if (f.ppc_package)
	{
		f.ppc_package.onchange = ppc_package_handle;
		if(f.ppc_package.value==2){
			f.ppc_setup_fee.value = "";
			f.ppc_setup_fee.disabled = "disabled";
		}
	}
	
	if (f.seo_package)
	{
		f.seo_package.onchange = seo_package_handle;
                f.seo_blog.onchange = seo_blog_handle;
                f.seo_blog_mgmt.onchange = seo_blog_mgmt_handle;
	}
	
	if (f.smo_package)
	{
		f.smo_package.onchange = smo_package_handle;
	}
	
	if (f.slb_package)
	{
		f.slb_package.onchange = slb_package_handle;
	}
	
	if (f.wd_package)
	{
		switch (f.wd_package.value){
			case '0': f.wd_package_amount.value = ''; break;
			case '1': f.wd_package_amount.value = '1995'; break;
			case '2': f.wd_package_amount.value = '3495'; break;
			case '3': f.wd_package_amount.value = '4995'; break;
		}
		f.wd_package.onchange = wd_package_handle;
	}
	
	if (f.wd_landing_page)
	{
		if(f.wd_landing_page.checked){
			f.wd_landing_page_testing.disabled = "";
		} else {
			f.wd_landing_page_testing.disabled = "disabled";
		}
		f.wd_landing_page.onchange = wd_landing_page_handle;
	}
	
}

window.add_onload_func(prospect_init);


// rn: functions for checking the input fields when creating/updating a prospect

function prospectFieldCheck(){

   var err = false;
   var numeric_err = false;

   $(':input.required').each(function(){
        if($(this).val()==""){
            $(this).addClass("error");
            err = true;
        } else {
            $(this).removeClass("error");
        }
   });
    
    $(':input.num').each(function(){
        if(isNaN($(this).val())){
            $(this).addClass("error");
            numeric_err = err = true;
        }  else {
            $(this).removeClass("error");
        }
    });

    if(err){
        alert("Error: Required fields found empty");
        if(numeric_err){
            alert("Error: Check highlighted fields for non numeric characters.");
        }
        return false;
    } else {
        
        html.hidden("go", "edit_prospect_submit");
        return true;
    }


}



// rn: functions for editing the payment info in e2

function checkPayment(id){
	if(document.getElementById(id).value == "credit"){
		document.getElementById('credit_card').style.display = "block";
		document.getElementById('check').style.display = "none";
	}
	else{
		document.getElementById('credit_card').style.display = "none";
		document.getElementById('check').style.display = "block";
	}
	
}

function checkSubmit(){
    
    if(!isInt(documnet.getElementById('check_number').value)){
        alert('invalid check numer');
        return false;
    }

    if(!isInt(documnet.getElementById('amount').value)){
        alert('invalid amount');
        return false;
    }

    return true;
}

// rn: edit fields dynamically in sap customization

function editField(field,id_tag,text,type,ta_class){

	
	var field_area = document.getElementById(field);
	var input = document.createElement("input");
	
	var edit_btn = document.getElementById(field+"_btn");
	var content = document.getElementById(field+"_text");
	  
	field_area.removeChild(content);
	field_area.removeChild(edit_btn);
	
	
	if(type=="par"){

	  input.type = "checkbox";
	  input.value = id_tag+"-0";
	  input.name = "default[]";
		
	  field_area.innerHTML += "<textarea class=\""+ta_class+"\" id=\""+id_tag+"\" name=\"sap_var-"+id_tag+"-0\" >"+text;
	  field_area.innerHTML += "</textarea>";
	  field_area.innerHTML += "<br>";
	  field_area.innerHTML += "Back to Default: ";
	  field_area.appendChild(input);
	  field_area.innerHTML += "<br>";
	  field_area.innerHTML += "<input type=\"submit\" value=\"Save\" a0=\"edit_proposal_submit\" />";
	  
	  
	} else if(type=="title"){

	  input.type = "checkbox";
	  input.value = id_tag+"-0";
	  input.name = "default[]";
		
	  field_area.innerHTML += "<textarea class=\""+ta_class+"\" id=\""+id_tag+"\" name=\"sap_var-"+id_tag+"-0\" >"+text;
	  field_area.innerHTML += "</textarea>";
	  field_area.innerHTML += "<br>";
	  field_area.innerHTML += "Back to Default: ";
	  field_area.appendChild(input);
	  field_area.innerHTML += "<br>";
	  field_area.innerHTML += "<input type=\"submit\" value=\"Save\" a0=\"edit_proposal_submit\" />";
	  
	  
	}
	
	else if(type=="list"){
		
		var list_items=text.split("|");
		
		// create a div that holds the textareas
		field_area.innerHTML += "<ul id=\""+field+"_list\" class=\"ta_list\" >";
		var inner_field_id = field+"_list";
		var inner_field_area = document.getElementById(inner_field_id);
		
		var list_node;
		
		for(var i=1;i<list_items.length;i++){ //the last list item is trash

                        x = list_items[i-1];
                        matches = x.match(/{(.*)}(.*)$/);

                        list_id = matches[1];
                        list_text = matches[2];

                        if(isNaN(list_id)){
                            ta_class += " add"
                        }

			input.type = "checkbox";
	  		input.value = id_tag+i;
	  		input.name = "default[]";
			inner_field_area.innerHTML += "<li>";
			list_node = inner_field_area.childNodes[i-1];
			
			list_node.innerHTML += "<textarea class=\""+ta_class+"\" id=\""+id_tag+list_id+"\" name=\"sap_var-"+id_tag+list_id+"\" >"+list_text;
	  		list_node.innerHTML += "</textarea>";
			list_node.innerHTML += "<br>";
	 		list_node.innerHTML += "Back to Default: ";
	  		list_node.appendChild(input);
	  		list_node.innerHTML += "<br>";
			
			inner_field_area.innerHTML += "</li>";
		}
		field_area.innerHTML += "</ul>";
		field_area.innerHTML += "<input type=\"button\" value=\"Add List Item\" onclick=\"addField('"+inner_field_id+"','"+id_tag+"')\" />";	
		field_area.innerHTML += "<input type=\"submit\" value=\"Save\" a0=\"edit_proposal_submit\" />";	
	}
	
	e2.auto_action_submit($(field_area));
	 
}

function editContractTerms(target_id){
	
	var inner_field_id = target_id+"_list";
	
	var out = "<ul id='"+inner_field_id+"'>";
	
	$('#'+target_id+' .editable').each(function(){
		
		var id_tag = $(this).attr('id');
		var list_text = $(this).attr('text');
		
		out += "<li>";
		out += "<textarea class='list_big' name=\"sap_var-"+id_tag+"\" >"+list_text;
		out += "</textarea>";
		out += "<br>";
		out += "Back to Default: ";
		out += "<br>";
		out += "</li>";
	
	});
	
	out += "</ul>";
	out += "<input type=\"button\" value=\"Add List Item\" onclick=\"addField('"+inner_field_id+"','"+target_id+"-')\" />";	
	out += "<input type=\"submit\" value=\"Save\" a0=\"edit_proposal_submit\" />";
	
	$('#'+target_id).replaceWith(out);
	
	e2.auto_action_submit($('#contract_terms'));
}
	

// rn: add fields dynamically to sap customization

function addField(field,id_tag){
	
 	var field_area = document.getElementById(field);
 	var all_inputs = field_area.getElementsByClassName("add"); //Get all the added li fields in the given area.
        //
 	//Find the count of the last custom added element of the list. It will be in the format '<field><number>'.
        
 	var last_item = all_inputs.length;
        var count;
       
        count = "a"+Number(last_item+1);
        
        //count is now the number needed for the new field
	
	if(document.createElement) { //W3C Dom method.
	  var li = document.createElement("li");
	  var textarea = document.createElement("textarea");
	  var input = document.createElement("input");
	  
	  textarea.id = id_tag+count;
	  textarea.name = "sap_var-"+id_tag+count;
	  textarea.className = 'list add';
	  
	  input.type = "checkbox";
	  input.value = id_tag+count;
	  input.name = "default[]";
	  
	  li.appendChild(textarea);
	  li.innerHTML += "<br>";
	  li.innerHTML += "Back to Default: ";
	  li.appendChild(input);
	  li.innerHTML += "<br>";
	  
	  field_area.appendChild(li);
	  
	 }
	 
}

function mark_edit(elem){

	var name = elem.name;

	if(name.search(/sap_edit-/)==-1){
		elem.name = 'sap_edit-'+name;
		//alert(elem.name);
	}
}

// rn: add fields dynamically to default sap text

function addDefaultField(src_elem){

        field_elem = $(src_elem).parent();

        field_id = field_elem.attr("id");

        all_inputs = field_elem.children("ul").children("li");

 	last_item = all_inputs.length;

        count = Number(last_item+1);

        //count is now the number needed for the new field

        if(document.createElement) { //W3C Dom method.
            var li = document.createElement("li");
            var textarea = document.createElement("textarea");

            textarea.id = field_id+"-"+count;
            textarea.name = field_id+"-"+count;
            textarea.onchange=function(){mark_edit(this)};

            li.appendChild(textarea);

            field_elem.children("ul").append(li);

        }



}


function sales_new_clients_list()
{
	var self = this;
	
	this.init_data();
	$('#payments').table({
		data:this.data,
		cols:this.cols,
		sort_init:'date',
		sort_dir_init:'desc',
		show_download:'new-client-list',
		show_totals:true
	});
}

sales_new_clients_list.prototype.init_data = function()
{
	var i, j, row, payment, type, part_types, part_amounts, pids;
	
	this.cols = [
		{key:'client'},
		{key:'rep'},
		{key:'date'},
		{key:'total',format:'dollars'}
	];
	this.data = [];
	this.part_types = {};
	
	// a payment will come through multiple times if the first payment has multiple departments
	pids = {};
	for (i = 0; i < globals.payments.length; ++i)
	{
		payment = globals.payments[i];
		if (!pids[payment.id])
		{
			pids[payment.id] = 1;
			row = {
				_display_client:'<a href="'+e2.url('/account/service/'+payment.dept+'/billing/edit_payment?aid='+payment.aid+'&pid='+payment.id)+'" target="_blank">'+payment.name+'</a>',
				pid:payment.id,
				rep:(globals.reps[payment.client_id]) ? globals.reps[payment.client_id] : '',
				cl_id:payment.client_id,
				client:payment.name,
				date:payment.date,
				dept:payment.dept,
				total:payment.total
			};
			part_types = payment.part_types.split("\t");
			part_amounts = payment.part_amounts.split("\t");
			for (j = 0; j < part_types.length; ++j)
			{
				type = part_types[j];
				this.part_types[type] = 1;
				row[type] = part_amounts[j];
			}
			this.data.push(row);
		}
	}
	
	for (type in this.part_types)
	{
		this.cols.push({key:type,format:'dollars',calc:this.calc_part_amount});
	}
};

sales_new_clients_list.prototype.calc_part_amount = function(data, key)
{
	return (data[key]) ? data[key] : 0;
};

function sales_hierarchy()
{
	$('#hierarchy .hierarch_delete_a').bind('click', this, function(e){ e.data.delete_click($(this)); return false; });
}

sales_hierarchy.prototype.delete_click = function(a)
{
	var row = a.closest('tr'),
		parent = row.find('td:eq(1)').text(),
		child = row.find('td:eq(2)').text();
	
	if (confirm('Delete '+parent+' -> '+child+'?'))
	{
		$('#delete_hid').val(row.attr('hid'));
		e2.action_submit('action_delete_hierarch');
	}
};
