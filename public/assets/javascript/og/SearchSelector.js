var ogSearchSelector = {};

//get item template copy
ogSearchSelector.getItemTemplateCopy = function (container_id , template_id, data){
	var copy = $("#"+container_id+" > #searchSelectorItemTemplate"+template_id).clone();
		
	//load data to the copy
	ogSearchSelector.loadDataToItemTemplateCopy(copy, data);
	
	copy.removeClass("item-template");
		
	return copy;	
}

//get show more item
ogSearchSelector.getShowMoreItem = function (container_id , template_id){
	var copy = $("#"+container_id+" > #showMoreTemplate"+template_id).clone();
	
	copy.removeClass("item-template");
		
	return copy;	
}

//load data to item template copy
ogSearchSelector.loadDataToItemTemplateCopy = function (copy , data){	
	//for each param in data search by id(the id is the param name) on the copy and add the param value as a class
	for (var param in data) {
		switch (param) {
			case "style":
		    	for (var style in data[param]) {
		    		if(copy.attr('id') == style){
		    			copy.addClass(data[param][style]);
		    			copy.removeAttr("id");
		    		}else{
		    			copy.find("#"+style).addClass(data[param][style]);	
		    			copy.find("#"+style).removeAttr("id");
		    		}
				}		    	
		        break;
		    default:
		    	//default append content 
		    	copy.find("."+param).append(data[param]);
		} 
	}
		
}

ogSearchSelector.resetLimit = function (container_id, limit){
	if(typeof limit=="undefined"){
		var first_limit = $("#"+container_id+"-input-first-limit").val();
		$("#"+container_id+"-input-limit").val(first_limit);
	}else{
		$("#"+container_id+"-input-limit").val(limit);
	}	
}

//Jquery autocomplete
ogSearchSelector.init = function (genid, container_id, extra_param, search_func, select_function, search_minLength){
	$("#"+container_id+"-input").autocomplete({
	   		minLength: search_minLength,
	   		source: function(request, callback){
	            var searchParam  = request.term;
	            search_func(genid, container_id, extra_param, searchParam, callback);
	        },
	   		focus: function( event, ui ) {
	   			$("#"+container_id+"-input").val( ui.item.label );
	   			return false;
	   		},
	   		select: function( event, ui ) {
	   			$("#"+container_id+"-input").val( ui.item.label ); 
	   			select_function(genid, container_id, extra_param, ui.item);
	   			return false;
	   		}
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
			return $( "<li>" )
	   		.append( item.desc )
	   		.appendTo( ul );
	};
	
	$("#"+container_id+"-input").on( "click", function() {
		//fire the search
		$( this ).keydown();
	});
	
	$("#"+container_id+"-ico-search-m").on( "click", function() {
		//fire the search		
		$( this ).siblings( ".search-input" ).keydown();
	});
}

/*
 * Custom functions
 * define here your own search function for the search selector
 * define here your own onclick function for each item on the result list on the search selector
 **/

//Member search
ogSearchSelector.searchMember = function (genid, container_id, dimension_id, search_text, callback){
	var template_id = 1;
	var result_limit = parseInt($("#"+container_id+"-input-limit").val());	
	var callback_func = function (data){
		if (typeof data.members != "undefined") {
			var items = [];
			for (var prop in data.members) {  
				var mem = data.members[prop];
				var dim_id = data.dimension_id;
								
				var searchResultInfo = mem.name;
				
				//add breadcrumb
				if(mem.parent > 0){
					var mpath_aux = {};
					mpath_aux[dim_id] = {};
					mpath_aux[dim_id][mem.id] = mem;
					text = og.getCrumbHtml(mpath_aux,false,null,false,null,false);
					
					searchResultInfo += "<br>" + text;
				}
				
				//item row height
				rowHeight = data.row_class;
								
				//build the item
				var result = {
								searchResultInfo: searchResultInfo
							};
				
				
				result["style"] = {
								searchSelectorItemTemplate1 : rowHeight,
								searchResultImgTemplate1 : mem.iconCls								
							};			
				
				var item_desc = ogSearchSelector.getItemTemplateCopy(container_id, template_id, result);
				
				var item =  {
					 value: mem.id,
					 label: mem.name,
					 desc: item_desc	
					 };	
				items.push(item);				
			}
			
			//show more
			if(data.show_more){
				var last_item =  {
						 value: 'more',
						 label: search_text,
						 limit: items.length + 5,
						 desc: ogSearchSelector.getShowMoreItem(container_id, template_id)	
						 };	
				
				items.push(last_item);
			}
			
			callback(items);
		}		
	}

	var search_params = {};	
	search_params.text = search_text;
	search_params.start = 0;
	search_params.limit = result_limit;
	search_params.order = 'name';
	search_params.parents = 0;
	
	if(search_text.length == 0){
		search_params.random = 1;
	}
	og.searchMemberOnServer(dimension_id, search_params, callback_func);
	
	ogSearchSelector.resetLimit(container_id);
}
//On member select
ogSearchSelector.onItemMemberSelect = function (genid, container_id, dimension_id, item){
	var member_id = item.value;
	if(member_id != "more"){
		member_selector.add_relation(dimension_id, genid, member_id);		
	}else if (member_id == "more"){
		$("#"+container_id+"-input").val(item.label);
		
		//increase the limit
		ogSearchSelector.resetLimit(container_id, item.limit);
		
		//fire the search
		$("#"+container_id+"-input").keydown();
	}	
}
//END Member
