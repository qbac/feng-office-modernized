/*
 * dims array all dimensions with members for this breadcrumb
 * draw_all_members bool if true draw all members (breadcrumb_member_count preference ignored)
 * skipped_dimensions array with all dimensions to skip in this breadcrumb
 * show_archived 
 * fixed_mem_len int this int indicate the max length for the name for each member
 * 
 * return html 
 * */
og.getRealCrumbHtml = function(dims, draw_all_members, skipped_dimensions, show_archived, fixed_mem_len, show_link) {
	var html = '';
	var dim_index = 0;
	var max_members_per_dim = og.preferences['breadcrumb_member_count'];
	for (x in dims) {
		if (isNaN(x)) continue;
		
		var skip_this_dimension = false;
		if (skipped_dimensions) {
			for (sd in skipped_dimensions) {
				if (skipped_dimensions[sd] == x) {
					skip_this_dimension = true;
					break;
				}
			}
		}
		if (skip_this_dimension) continue;
		
		var members = dims[x];
		var inner_html = "";
		var title = "";
		var total_texts = 0;
		var all_texts = [];
		
		for (id in members) {
			id = parseInt(id);
			if (isNaN(id)) continue;
			
			var m = members[id];
			var extra_params = {
					'draw_all_members':draw_all_members,
					'skipped_dimensions':skipped_dimensions,
					'show_archived':show_archived,
					'fixed_mem_len':fixed_mem_len,
					'show_link':show_link								
					};
			var texts = og.getMemberTextsFromOgDimensions(id, true, og.replaceAllBreadcrumbForThisMember, extra_params);
			
			if (texts.length == 0){				
				texts.push({id:id, text:m.name, ot:m.ot, c:m.c});
			}
			total_texts += texts.length;
				
			all_texts[id] = texts;			
		}
		
		if (fixed_mem_len && !isNaN(fixed_mem_len)) {
			max_len = fixed_mem_len;
		} else {
			if (total_texts == 1) max_len = 13
			else if (total_texts < 3) max_len = 9;
			else if (total_texts < 5) max_len = 5;
			else max_len = 4;
		}
		
		breadcrumb_count = 0;
		for (id in members) {
			if (isNaN(id)) continue;
			texts = all_texts[id];
			
			if (texts.length > 0) {
				breadcrumb_count++;
			}
			if (!draw_all_members && breadcrumb_count > max_members_per_dim) break;
			
			if (title != "" && breadcrumb_count <= max_members_per_dim) title += '- ';
			var color = members[id]['c'];
			var member_path_span = '<span class="bread-crumb-'+ id +' member-path og-wsname-color-'+ color +'">';
			var member_path_content = "";
			
			for (i=texts.length-1; i>=0; i--) {
				var text = texts[i].text;
				text = text.replace("&amp;","&");
				if (i>0) {
					str = text.length > max_len ? text.substring(0, max_len-3) + ".." : text;
				} else {
					str = text.length > 12 ? text.substring(0, 10) + ".." : text;
				}
				if (breadcrumb_count <= max_members_per_dim) {
					title += texts[i].text + (i>0 ? "/" : " ");
				}
				
				var onclick = "return false;";
				if (og.additional_on_dimension_object_click[texts[i].ot]) {
					onclick = og.additional_on_dimension_object_click[texts[i].ot].replace('<parameters>', texts[i].id);
				}   
				
				if(show_link){
					var link = '<a href="#" onclick="' + onclick + '">' + str + '</a>';
				}else{
					var link = str;
				}
				
				
				member_path_content += link;
				if (i>0) member_path_content += "/";
			}
			member_path_span += member_path_content + '</span>';
			
			if (member_path_content != '') inner_html += member_path_span;
		}
		
		if (members['total'] > max_members_per_dim) {
			title += lang('and number more', (members['total'] - max_members_per_dim));
		}
		
		if (inner_html != "") html += '<span class="member-path" title="'+title+'">' + inner_html + '</span>';
		dim_index++;
	}
		
	return html;
}

/*
 * This function return all the breadcrumbs for a set of members
 * @dims array all dimensions with members for this breadcrumb
 * @draw_all_members bool if true draw all members (breadcrumb_member_count preference ignored)
 * @skipped_dimensions array with all dimensions to skip in this breadcrumb
 * @show_archived 
 * @fixed_mem_len int this int indicate the max length for the name for each member
 * 
 * return html 
 * */
og.getCrumbHtml = function(dims, draw_all_members, skipped_dimensions, show_archived, fixed_mem_len, show_link) {
	var all_bread_crumbs = "";
	
	if (typeof show_link == "undefined") {
		show_link = true;
	}
	
	for (x in dims) {
		if (isNaN(x)) continue;
		var dim = {};
		var empty_bread_crumbs = "";
		var members = dims[x];
		
		for (id in members) {
			if (isNaN(id)) continue;
			//get the member from og.dimensions and if is not there get it form the server and execute og.replaceAllBreadcrumbForThisMember on the callback
			var extra_params = {
								'draw_all_members':draw_all_members,
								'skipped_dimensions':skipped_dimensions,
								'show_archived':show_archived,
								'fixed_mem_len':fixed_mem_len,
								'show_link':show_link								
								};
			var members = og.getMemberFromOgDimensions(id, false, og.replaceAllBreadcrumbForThisMember, extra_params);
			
			if (members.length > 0){
				var member = members[0];
				
				if (typeof dim[member.dimension_id] == "undefined") {
					dim[member.dimension_id] = {};
				}
				
				member_info ={
				 			"id":member.id,
				 			"ot":member.object_type_id,
				 			"c":member.color,
				 			"name":member.name
				};
				dim[member.dimension_id][member.id] = member_info;
			}else{
				//return a target to reload on the callback after get the member from the server
				empty_bread_crumbs += '<span class="member-path"><span class="bread-crumb-'+ id +' member-path"></span></span>';
			}
		}
		all_bread_crumbs += og.getRealCrumbHtml(dim, draw_all_members, skipped_dimensions, show_archived, fixed_mem_len, show_link);
		all_bread_crumbs += empty_bread_crumbs;
	}
	
	return all_bread_crumbs;
}

//this function is used as a callback if a member is not in og.dimensions
og.replaceAllBreadcrumbForThisMember = function(dimension_id ,member, extra_params) {
		
	//replace all breadcrumb for this member
	var dim = {};
	dim[dimension_id] = {};
	member_info ={
				"id":member.id,
				"ot":member.object_type_id,
				"c":member.color,
				"name":member.name
	};
	dim[dimension_id][member.id] = member_info;
	   
	$(".bread-crumb-"+member.id).replaceWith(og.getCrumbHtml(dim,extra_params.draw_all_members,extra_params.skipped_dimensions,extra_params.show_archived,extra_params.fixed_mem_len,extra_params.show_link));
}

og.getCrumbHtmlWithoutLinksMemPath = function(dims, draw_all_members, skipped_dimensions, show_archived, fixed_mem_len , total_length, genid) {
	var html = '';
	var dim_index = 0;
	var max_members_per_dim = og.preferences['breadcrumb_member_count'];
	for (x in dims) {
		if (isNaN(x)) continue;
		
		var skip_this_dimension = false;
		if (skipped_dimensions) {
			for (sd in skipped_dimensions) {
				if (skipped_dimensions[sd] == x) {
					skip_this_dimension = true;
					break;
				}
			}
		}
		if (skip_this_dimension) continue;
		
		var members = dims[x];
		var inner_html = "";
		var title = "";
		var total_texts = 0;
		var all_texts = [];
		var total_text_length = 0;
		var total_texts_in_Crumb = 0;
		var important_member_name = "";
		
		for (id in members) {
			id = parseInt(id);
			if (isNaN(id)) continue;
			var m = members[id];
			if (!m.archived) {
				var callback_extra_params = {genid:genid}; 
				var texts = og.getMemberTextsFromOgDimensions(id, true, og.replaceCrumbHtmlWithoutLinks, callback_extra_params);				
			} else {
				var texts = [];
				texts.push({id:m.id, text:m.name, ot:m.ot, c:m.c});
			}
			if (texts.length == 0 && show_archived){
				texts.push({id:id, text:m.name, ot:m.ot, c:m.c});
			}
			total_texts += texts.length;
			
			all_texts[id] = texts;
			
			if(total_length && !isNaN(total_length)){
				for (x in texts) {
					total_text_length += texts[x].length;
				    total_texts_in_Crumb++;
				}
			}
		}
		
		if (fixed_mem_len && !isNaN(fixed_mem_len)) {
			max_len = fixed_mem_len;
		} else {
			if (total_texts == 1) max_len = 13
			else if (total_texts < 3) max_len = 9;
			else if (total_texts < 5) max_len = 5;
			else max_len = 4;
			
			if(total_length && !isNaN(total_length)){
				max_len = Math.floor(total_length/total_texts_in_Crumb);
			}
		}
		
		
		breadcrumb_count = 0;
		for (id in members) {
			if (isNaN(id)) continue;
			texts = all_texts[id];
			
			if (texts.length > 0) {
				breadcrumb_count++;
			}
			if (!draw_all_members && breadcrumb_count > max_members_per_dim) break;
			
			if (title != "" && breadcrumb_count <= max_members_per_dim) title += '- ';
			var color = members[id]['c'];
			var member_path_span = '<span class="member-path og-wsname-color-'+ color +'">';
			var member_path_content = "";
			
			for (i=texts.length-1; i>=0; i--) {
				var text = texts[i].text;
				text = text.replace("&amp;","&");
				if (i>0) {
					str = text.length > max_len ? text.substring(0, max_len-3) + ".." : text;
				} else {
					min_len = max_len < 12 ?  10 : max_len-3;					
					str = (text.length > 12 && text.length > max_len) ? text.substring(0, min_len) + ".." : text;
					important_member_name = text.substring(0, total_length);
				}
				if (breadcrumb_count <= max_members_per_dim) {
					title += texts[i].text + (i>0 ? "/" : " ");
				}
				
				var onclick = "return false;";
				if (og.additional_on_dimension_object_click[texts[i].ot]) {
					onclick = og.additional_on_dimension_object_click[texts[i].ot].replace('<parameters>', texts[i].id);
				}                                
				
				member_path_content += str;
				
				if (i>0) member_path_content += "/";
			}
						
			if(member_path_content.length > total_length && max_len <= 3){
				member_path_content = ".../"+important_member_name;
			}
			member_path_span += member_path_content + '</span>';
			
			if (member_path_content != '') inner_html += member_path_span;
		}
		
		if (members['total'] > max_members_per_dim) {
			title += lang('and number more', (members['total'] - max_members_per_dim));
		}
		
		if (inner_html != "") html += '<span class="member-path" title="'+title+'">' + inner_html + '</span>';
		dim_index++;
	}
	
	return html;
}

//return the member bredcrumb without links. Length of bredcrumb is calculate from completePath contenedor
og.getCrumbHtmlWithoutLinks = function (member_id, dimension_id, genid) {
	member_id = parseInt(member_id);
	if (isNaN(member_id)) return false;
	dimension_id = parseInt(dimension_id);
	if (isNaN(dimension_id)) return false;
	
	//calculate bredcrumb width
	width = $("#"+genid+"selected-member"+member_id+" .completePath").width();
	if(width == null || width == 0){
		width = 240;
	}
	//cambiar esta funcion $hola
	var callback_extra_params = {genid:genid}; 
	var texts = og.getMemberTextsFromOgDimensions(member_id, false, og.replaceCrumbHtmlWithoutLinks, callback_extra_params);
	
	bredcrumb_total_length = width / 7;
	
	if(texts.length > 0){
		var member = {};
		member[member_id] = texts[0];
		var member_path = {};
		member_path[dimension_id] = member;
		mem_path = og.getCrumbHtmlWithoutLinksMemPath(member_path, false, null,false,null,bredcrumb_total_length,genid);
		
		return mem_path;
	}else{
		return false;
	}
}

//this function is used as a callback if a member is not in og.dimensions
og.replaceCrumbHtmlWithoutLinks = function(dimension_id ,member, extra_params) {
	//replace breadcrumb for this member
	var html = og.getCrumbHtmlWithoutLinks(member.id,dimension_id,extra_params.genid);	
	$("#"+ extra_params.genid +"selected-member"+ member.id +" > .completePath").replaceWith(html);
}

