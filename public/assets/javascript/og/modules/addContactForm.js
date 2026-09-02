og.reloadCompanies = function ( context, genid ){
	Ext.Ajax.request({
		url: og.getUrl('contact', 'list_companies', { 'ajax':true, 'context': Ext.util.JSON.encode(context) }),
		success:  function(result, request) {
			var jsonData = Ext.util.JSON.decode(result.responseText);
			var companies = jsonData.companies ;
			
			var combo = document.getElementById(genid+"profileFormCompany");
			firstOption = combo.options[0];
			combo.innerHTML = '';
			
			combo.appendChild(firstOption);
			for (var i = 0 ; i < companies.length ; i++ ) {
				var option = document.createElement('option') ;
				option.innerHTML = companies[i].name ;
				option.value = companies[i].value ;
				combo.appendChild(option);
			}
			
			
		}
	});
}


og.addNewCompany = function(genid){
	var show = document.getElementById(genid + 'new_company').style.display == 'none';
	document.getElementById(genid + 'new_company').style.display = show ? 'block':'none';
	document.getElementById(genid + 'existing_company').style.display = show? 'none': 'block';
	document.getElementById(genid + 'hfIsNewCompany').value = show;
	document.getElementById(genid + 'duplicateCompanyName').style.display = 'none';
	document.getElementById(genid + 'profileFormNewCompanyName').value = '';
	if (show) document.getElementById(genid + 'profileFormNewCompanyName').focus();
	Ext.get(genid + 'submit1').dom.disabled = false;
	Ext.get(genid + 'submit2').dom.disabled = false;
};

og.checkNewCompanyName = function(genid) {
	var fff = document.getElementById(genid + 'profileFormNewCompanyName');
	var name = fff.value.toUpperCase();
	document.getElementById(genid + 'duplicateCompanyName').style.display = 'none';
	document.getElementById(genid + 'duplicateCompanyName').innerHTML = '';
	
	var select = document.getElementById(genid + 'profileFormCompany');
	for (var i = 1; i < select.options.length; i++){
		if (select.options[i].text.toUpperCase() == name){
			document.getElementById(genid + 'duplicateCompanyName').innerHTML = lang('duplicate company name', select.options[i].text, genid, i);
			document.getElementById(genid + 'companyInfo').style.display="none";
			document.getElementById(genid + 'duplicateCompanyName').style.display = 'block';
			Ext.get(genid + 'submit1').dom.disabled = true;
			Ext.get(genid + 'submit2').dom.disabled = true;
			document.getElementById(genid + 'duplicateCompanyName').focus();
			return;
		}
	}		
	Ext.get(genid + 'submit1').dom.disabled = false;
	Ext.get(genid + 'submit2').dom.disabled = false;
	document.getElementById(genid + 'companyInfo').style.display="block";
		
};

og.selectCompany = function(genid, index) {
	var select = document.getElementById(genid + 'profileFormCompany');
	select.selectedIndex = index;
	og.addNewCompany(genid);
	og.companySelectedIndexChanged(genid);
};

og.companySelectedIndexChanged = function(genid,data_js){
	select = document.getElementById(genid + 'profileFormCompany');
	Ext.get(genid + 'submit1').dom.disabled = true;
	Ext.get(genid + 'submit2').dom.disabled = true;
	
    og.openLink(og.getUrl('contact','get_company_data', {id: select.options[select.selectedIndex].value}), {
    	caller:this,
    	callback: function(success, data) {
    		if (success) {
				Ext.get(genid + 'submit1').dom.disabled = false;
				Ext.get(genid + 'submit2').dom.disabled = false;
				
    			if (data.id > 0){
	    			document.getElementById(genid + 'profileFormWAddress').value = data_js['adress'] ? data_js['adress'] :  data.address;
	    			document.getElementById(genid + 'profileFormWCity').value = data_js['city'] ? data_js['city'] : data.city;
	    			document.getElementById(genid + 'profileFormWState').value = data_js['state'] ? data_js['state'] : data.state;
					var list = document.getElementById(genid + 'profileFormWCountry');
					for (var i = 0; i < list.options.length; i++)
						if (list.options[i].value == data.country){
							list.selectedIndex = i;
							break;
						}
	    			document.getElementById(genid + 'profileFormWZipcode').value = data_js['zipCode'] ? data_js['zipCode'] : data.zipcode;
	    			document.getElementById(genid + 'profileFormWWebPage').value = data_js['web'] ? data_js['web'] : data.webpage;
	    			document.getElementById(genid + 'profileFormWPhoneNumber').value = data_js['phone'] ? data_js['phone'] : data.phoneNumber;
	    			document.getElementById(genid + 'profileFormWFaxNumber').value = data_js['fax'] ? data_js['fax'] : data.faxNumber;
	    			
	    		}else{
	    			var text = "";
	    			document.getElementById(genid + 'profileFormWAddress').value = data_js['adress'] ? data_js['adress'] :  text;
	    			document.getElementById(genid + 'profileFormWCity').value = data_js['city'] ? data_js['city'] : text;
	    			document.getElementById(genid + 'profileFormWState').value = data_js['state'] ? data_js['state'] : text;
	    			document.getElementById(genid + 'profileFormWZipcode').value = data_js['zipCode'] ? data_js['zipCode'] : text;
	    			document.getElementById(genid + 'profileFormWWebPage').value = data_js['web'] ? data_js['web'] : text;
	    			document.getElementById(genid + 'profileFormWPhoneNumber').value = data_js['phone'] ? data_js['phone'] : text;
	    			document.getElementById(genid + 'profileFormWFaxNumber').value = data_js['fax'] ? data_js['fax'] : text;
	    		}
    		}
    	}
    });
}

og.addContactTypeChanged = function(type, genid){
	if(type == 0){
		//document.getElementById(genid + 'hfType').value = 0;
		document.getElementById(genid + 'non-registered-person-form').style.display = '';
		document.getElementById(genid + 'registered-person-form').style.display = 'none';
		//, 'onclick' => "$('.non-registered-add-person-form').slideToggle();$('#non-registered-add-person-form-show').show();"
	}else{
		//document.getElementById(genid + 'hfType').value = 1;
		document.getElementById(genid + 'non-registered-person-form').style.display = 'none';
		document.getElementById(genid + 'registered-person-form').style.display = '';
	}
}



og.markAsDeleted = function(del_el, container_id, input_id) {
	$('#'+input_id+'_deleted').val(1);
	$('#'+container_id).css('background-color', '#ECC');
	del_el.style.display = 'none';

	$('#'+container_id+' input').attr('disabled', 'disabled');
	$('#'+container_id+' select').attr('disabled', 'disabled');

	$('#'+input_id+'_deleted').removeAttr('disabled');
	$('#'+input_id+'_id').removeAttr('disabled');

	$('#'+container_id+' .undo-delete').css('display', '');
}
og.undoMarkAsDeleted = function(undo_el, container_id, input_id) {
	$('#'+input_id+'_deleted').val(0);
	$('#'+container_id).css('background-color', '#fff');
	undo_el.style.display = 'none';

	$('#'+container_id+' input').removeAttr('disabled');
	$('#'+container_id+' select').removeAttr('disabled');

	$('#'+container_id+' .delete-link').css('display', '');
}

og.renderTelephoneTypeSelector = function(id, name, container_id, selected_value) {
	
	var select = $('<select name="'+name+'" id="'+id+'"></select>');
	for (var i=0; i<og.telephone_types.length; i++) {
		var type = og.telephone_types[i];
		var option = $('<option></option>');
		option.attr('value', type.id);
		if (selected_value == type.id) option.attr('selected', 'selected');
		option.text(type.name);
		select.append(option);
	}
	$('#'+container_id).empty().append(select);
}

og.renderTelephoneInput = function(id, name, container_id, sel_type, sel_number, sel_name, sel_id) {
	if (!sel_number) sel_number = '';
	if (!sel_name) sel_name = '';
	if (!sel_id) sel_id = 0;

	$('#'+container_id).append('<input type="hidden" name="'+name+'[id]" id="'+id+'_id" value="'+sel_id+'" />');
	$('#'+container_id).append('<input type="hidden" name="'+name+'[deleted]" id="'+id+'_deleted" value="0" />');
	
	$('#'+container_id).append('<span id="'+id+'_type"></span>');
	og.renderTelephoneTypeSelector(id+'_type', name+'[type]', id+'_type', sel_type);

	var number_input = $('<input name="'+name+'[number]" id="'+id+'_number" value="'+sel_number+'" placeholder="'+lang('phone number')+'"/>');
	$('#'+container_id).append(number_input);

	var name_input = $('<input name="'+name+'[name]" id="'+id+'_name" value="'+sel_name+'" placeholder="'+lang('name')+'"/>');
	$('#'+container_id).append(name_input);

	var delete_link = $('<a href="#" onclick="og.markAsDeleted(this, \''+container_id+'\', \''+id+'\');" class="coViewAction ico-delete delete-link" title="'+lang('delete')+'">'+lang('delete')+'</a>');
	$('#'+container_id).append(delete_link);
	var undo_delete_link = $('<a href="#" onclick="og.undoMarkAsDeleted(this, \''+container_id+'\', \''+id+'\');" class="coViewAction ico-undo undo-delete" style="display:none;" title="'+lang('undo')+'">'+lang('undo')+'</a>');
	$('#'+container_id).append(undo_delete_link);
}

og.addNewTelephoneInput = function(container_id, pre_id, sel_type, sel_number, sel_name, sel_id) {
	if (!pre_id) pre_id = 'contact';
	if (!og.telephoneCount) og.telephoneCount = 0;
	var id = pre_id+'Phone_' + og.telephoneCount;
	var name = pre_id+'[phone][' + og.telephoneCount + ']';

	$('#'+container_id).append('<div id="'+ container_id + og.telephoneCount +'" class="phone-input-container"></div>');
	
	og.renderTelephoneInput(id, name, container_id + og.telephoneCount, sel_type, sel_number, sel_name, sel_id);

	og.telephoneCount++;
}

og.renderAddressTypeSelector = function(id, name, container_id, selected_value) {
	
	var select = $('<select name="'+name+'" id="'+id+'"></select>');
	for (var i=0; i<og.address_types.length; i++) {
		var type = og.address_types[i];
		var option = $('<option></option>');
		option.attr('value', type.id);
		if (selected_value == type.id) option.attr('selected', 'selected');
		option.text(type.name);
		select.append(option);
	}
	$('#'+container_id).empty().append(select);
}

og.renderAddressInput = function(id, name, container_id, sel_type, sel_data) {
	if (!sel_data) sel_data = {};
	if (!sel_data.id) sel_data.id = 0;
	if (!sel_data.street) sel_data.street = '';
	if (!sel_data.city) sel_data.city = '';
	if (!sel_data.state) sel_data.state = '';
	if (!sel_data.zip_code) sel_data.zip_code = '';
	if (!sel_data.country) sel_data.country = '';
	
	$('#'+container_id).append('<input type="hidden" name="'+name+'[id]" id="'+id+'_id" value="'+sel_data.id+'" />');
	$('#'+container_id).append('<input type="hidden" name="'+name+'[deleted]" id="'+id+'_deleted" value="0" />');
	
	$('#'+container_id).append('<span id="'+id+'_type"></span>');
	og.renderAddressTypeSelector(id+'_type', name+'[type]', id+'_type', sel_type);

	var address_input = $('<input name="'+name+'[street]" id="'+id+'_street" value="'+sel_data.street+'" placeholder="'+lang('address')+'"/>');
	$('#'+container_id).append(address_input);

	var city_input = $('<input name="'+name+'[city]" id="'+id+'_city" value="'+sel_data.city+'" placeholder="'+lang('city')+'"/>');
	$('#'+container_id).append(city_input);

	var state_input = $('<input name="'+name+'[state]" id="'+id+'_state" value="'+sel_data.state+'" placeholder="'+lang('state')+'"/>');
	$('#'+container_id).append(state_input);

	var zip_code_input = $('<input name="'+name+'[zip_code]" id="'+id+'_zip_code" value="'+sel_data.zip_code+'" placeholder="'+lang('zip_code')+'"/>');
	$('#'+container_id).append(zip_code_input);

	var select_country = $('<select name="'+name+'[country]" id="'+id+'_country"></select>');
	$('#'+container_id).append(select_country);
	$('#template_select_country option').clone().appendTo('#'+id+'_country');
	if (sel_data.country != '') {
		var selc = document.getElementById(id+'_country');
		for (var i=0; i<selc.options.length; i++) {
			if (selc.options[i].value == sel_data.country) selc.options[i].setAttribute('selected','selected');
		}
	}

	var delete_link = $('<a href="#" onclick="og.markAsDeleted(this, \''+container_id+'\', \''+id+'\');" class="coViewAction ico-delete delete-link" title="'+lang('delete')+'">'+lang('delete')+'</a>');
	$('#'+container_id).append(delete_link);
	var undo_delete_link = $('<a href="#" onclick="og.undoMarkAsDeleted(this, \''+container_id+'\', \''+id+'\');" class="coViewAction ico-undo undo-delete" style="display:none;" title="'+lang('undo')+'">'+lang('undo')+'</a>');
	$('#'+container_id).append(undo_delete_link);
}

og.addNewAddressInput = function(container_id, pre_id, sel_type, sel_data) {
	if (!pre_id) pre_id = 'contact';
	if (!og.addressCount) og.addressCount = 0;
	var id = pre_id + 'Address_' + og.addressCount;
	var name = pre_id + '[address][' + og.addressCount + ']';

	$('#'+container_id).append('<div id="'+ container_id + og.addressCount +'" class="address-input-container"></div>');
	
	og.renderAddressInput(id, name, container_id + og.addressCount, sel_type, sel_data);

	og.addressCount++;
}


og.renderWebpageTypeSelector = function(id, name, container_id, selected_value) {
	
	var select = $('<select name="'+name+'" id="'+id+'"></select>');
	for (var i=0; i<og.webpage_types.length; i++) {
		var type = og.webpage_types[i];
		var option = $('<option></option>');
		option.attr('value', type.id);
		if (selected_value == type.id) option.attr('selected', 'selected');
		option.text(type.name);
		select.append(option);
	}
	$('#'+container_id).empty().append(select);
}

og.renderWebpageInput = function(id, name, container_id, sel_type, sel_url, sel_id) {
	if (!sel_url) sel_url = '';
	if (!sel_id) sel_id = 0;

	$('#'+container_id).append('<input type="hidden" name="'+name+'[id]" id="'+id+'_id" value="'+sel_id+'" />');
	$('#'+container_id).append('<input type="hidden" name="'+name+'[deleted]" id="'+id+'_deleted" value="0" />');
	
	$('#'+container_id).append('<span id="'+id+'_type"></span>');
	og.renderWebpageTypeSelector(id+'_type', name+'[type]', id+'_type', sel_type);

	var webpage_input = $('<input name="'+name+'[url]" id="'+id+'_url" value="'+sel_url+'" placeholder="'+lang('webpage')+'"/>');
	$('#'+container_id).append(webpage_input);

	var delete_link = $('<a href="#" onclick="og.markAsDeleted(this, \''+container_id+'\', \''+id+'\');" class="coViewAction ico-delete delete-link" title="'+lang('delete')+'">'+lang('delete')+'</a>');
	$('#'+container_id).append(delete_link);
	var undo_delete_link = $('<a href="#" onclick="og.undoMarkAsDeleted(this, \''+container_id+'\', \''+id+'\');" class="coViewAction ico-undo undo-delete" style="display:none;" title="'+lang('undo')+'">'+lang('undo')+'</a>');
	$('#'+container_id).append(undo_delete_link);
}

og.addNewWebpageInput = function(container_id, pre_id, sel_type, sel_url, sel_id) {
	if (!pre_id) pre_id = 'contact';
	if (!og.webpageCount) og.webpageCount = 0;
	var id = pre_id+'Webpage_' + og.webpageCount;
	var name = pre_id + '[webpage][' + og.webpageCount + ']';

	$('#'+container_id).append('<div id="'+ container_id + og.webpageCount +'" class="webpage-input-container"></div>');
	
	og.renderWebpageInput(id, name, container_id + og.webpageCount, sel_type, sel_url, sel_id);

	og.webpageCount++;
}



og.renderEmailTypeSelector = function(id, name, container_id, selected_value) {
	
	var select = $('<select name="'+name+'" id="'+id+'"></select>');
	for (var i=0; i<og.email_types.length; i++) {
		var type = og.email_types[i];
		var option = $('<option></option>');
		option.attr('value', type.id);
		if (selected_value == type.id) option.attr('selected', 'selected');
		option.text(type.name);
		select.append(option);
	}
	$('#'+container_id).empty().append(select);
}

og.renderEmailInput = function(id, name, container_id, sel_type, sel_address, sel_id) {
	if (!sel_address) sel_address = '';
	if (!sel_id) sel_id = 0;

	$('#'+container_id).append('<input type="hidden" name="'+name+'[id]" id="'+id+'_id" value="'+sel_id+'" />');
	$('#'+container_id).append('<input type="hidden" name="'+name+'[deleted]" id="'+id+'_deleted" value="0" />');
	
	$('#'+container_id).append('<span id="'+id+'_type"></span>');
	og.renderEmailTypeSelector(id+'_type', name+'[type]', id+'_type', sel_type);

	var email_input = $('<input name="'+name+'[email_address]" id="'+id+'_email_address" value="'+sel_address+'" placeholder="'+lang('email address')+'"/>');
	$('#'+container_id).append(email_input);

	var delete_link = $('<a href="#" onclick="og.markAsDeleted(this, \''+container_id+'\', \''+id+'\');" class="coViewAction ico-delete delete-link" title="'+lang('delete')+'">'+lang('delete')+'</a>');
	$('#'+container_id).append(delete_link);
	var undo_delete_link = $('<a href="#" onclick="og.undoMarkAsDeleted(this, \''+container_id+'\', \''+id+'\');" class="coViewAction ico-undo undo-delete" style="display:none;" title="'+lang('undo')+'">'+lang('undo')+'</a>');
	$('#'+container_id).append(undo_delete_link);
}

og.addNewEmailInput = function(container_id, pre_id, sel_type, sel_address, sel_id) {
	if (!pre_id) pre_id = 'contact';
	if (!og.emailCount) og.emailCount = 0;
	var id = pre_id+'Email_' + og.emailCount;
	var name = pre_id + '[emails][' + og.emailCount + ']';

	$('#'+container_id).append('<div id="'+ container_id + og.emailCount +'" class="email-input-container"></div>');
	
	og.renderEmailInput(id, name, container_id + og.emailCount, sel_type, sel_address, sel_id);

	og.emailCount++;
}