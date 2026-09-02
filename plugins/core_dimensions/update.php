<?php

/**
 * Add permissions for timeslots, templates and reports in persons dimension
 * @author Alvaro Torterola <alvaro.torterola@fengoffice.com>
 */
function core_dimensions_update_1_2() {
	DB::execute("
		INSERT INTO ".TABLE_PREFIX."dimension_object_type_contents (dimension_id, dimension_object_type_id, content_object_type_id, is_required, is_multiple)
		 SELECT (SELECT id FROM ".TABLE_PREFIX."dimensions WHERE code = 'feng_persons'), (SELECT id FROM ".TABLE_PREFIX."object_types WHERE name='person'), ot.id, 0, 1
		 FROM ".TABLE_PREFIX."object_types ot WHERE ot.type IN ('located')
		ON DUPLICATE KEY UPDATE dimension_id=dimension_id;
	");
	
	DB::execute("
		INSERT INTO ".TABLE_PREFIX."dimension_object_type_contents (dimension_id, dimension_object_type_id, content_object_type_id, is_required, is_multiple)
		 SELECT (SELECT id FROM ".TABLE_PREFIX."dimensions WHERE code = 'feng_persons'), (SELECT id FROM ".TABLE_PREFIX."object_types WHERE name='company'), ot.id, 0, 1
		 FROM ".TABLE_PREFIX."object_types ot WHERE ot.type IN ('located')
		ON DUPLICATE KEY UPDATE dimension_id=dimension_id;
	");
	
	DB::execute("
		INSERT INTO `".TABLE_PREFIX."contact_member_permissions` (`permission_group_id`, `member_id`, `object_type_id`, `can_write`, `can_delete`)
		 SELECT `c`.`permission_group_id`, `m`.`id`, `ot`.`id`, (`c`.`object_id` = `m`.`object_id`) as `can_write`, (`c`.`object_id` = `m`.`object_id`) as `can_delete`
		 FROM `".TABLE_PREFIX."contacts` `c` JOIN `".TABLE_PREFIX."members` `m`, `".TABLE_PREFIX."object_types` `ot`
		 WHERE `c`.`is_company`=0
		 	AND `c`.`user_type`!=0
		 	AND `ot`.`type` IN ('located')
		 	AND `m`.`dimension_id` IN (SELECT `id` FROM `".TABLE_PREFIX."dimensions` WHERE `code` = 'feng_persons')
		 	AND `c`.`object_id` = `m`.`object_id`
		ON DUPLICATE KEY UPDATE member_id=member_id;
	");
}

function core_dimensions_update_2_3() {
	DB::execute("
		UPDATE ".TABLE_PREFIX."dimensions SET permission_query_method='not_mandatory' WHERE code='feng_persons';
	");
}

function core_dimensions_update_3_4() {
	DB::execute("
		INSERT INTO `".TABLE_PREFIX."config_options` (`category_name`, `name`, `value`, `config_handler_class`, `is_system`, `option_order`) VALUES
			('system', 'hide_people_vinculations', '1', 'BoolConfigHandler', 1, 0)
		ON DUPLICATE KEY UPDATE name=name;
	");
}

function core_dimensions_update_4_5() {
	DB::execute("
		INSERT INTO `".TABLE_PREFIX."contact_member_permissions` (`permission_group_id`, `member_id`, `object_type_id`, `can_write`, `can_delete`)
		 SELECT `c`.`permission_group_id`, `m`.`id` as member_id, `ot`.`id` as object_type_id, 1, 1
		 FROM `".TABLE_PREFIX."contacts` `c` JOIN `".TABLE_PREFIX."members` `m`, `".TABLE_PREFIX."object_types` `ot`
		 WHERE `c`.`object_id`=m.object_id
		   AND `c`.`permission_group_id` > 0
		 	AND `ot`.`type` IN ('located')
		 	AND `m`.`dimension_id` IN (SELECT `id` FROM `".TABLE_PREFIX."dimensions` WHERE `code` = 'feng_persons')
		ON DUPLICATE KEY UPDATE ".TABLE_PREFIX."contact_member_permissions.member_id=".TABLE_PREFIX."contact_member_permissions.member_id;
	");
}

function core_dimensions_update_5_6() {
	DB::execute('
		UPDATE '.TABLE_PREFIX.'dimensions SET options = \'{"useLangs":true,"defaultAjax":{"controller":"dashboard", "action": "main_dashboard"},"quickAdd":{"formAction":"?c=contact&a=quick_add"}}\'
		WHERE code = \'feng_persons\';
	');
}

/**
 * template task and template mileston objects
 *
 */
function core_dimensions_update_6_7() {
	DB::execute("
		INSERT INTO ".TABLE_PREFIX."dimension_object_type_contents (dimension_id,dimension_object_type_id,content_object_type_id,is_required,is_multiple) VALUES
		((SELECT id FROM ".TABLE_PREFIX."dimensions WHERE code='feng_persons'), (SELECT id FROM ".TABLE_PREFIX."object_types WHERE name='person' LIMIT 1), (SELECT id FROM ".TABLE_PREFIX."object_types WHERE name='template_task' LIMIT 1),0,1),
		((SELECT id FROM ".TABLE_PREFIX."dimensions WHERE code='feng_persons'), (SELECT id FROM ".TABLE_PREFIX."object_types WHERE name='company' LIMIT 1), (SELECT id FROM ".TABLE_PREFIX."object_types WHERE name='template_task' LIMIT 1),0,1),
		((SELECT id FROM ".TABLE_PREFIX."dimensions WHERE code='feng_persons'), (SELECT id FROM ".TABLE_PREFIX."object_types WHERE name='person' LIMIT 1), (SELECT id FROM ".TABLE_PREFIX."object_types WHERE name='template_milestone' LIMIT 1),0,1),
		((SELECT id FROM ".TABLE_PREFIX."dimensions WHERE code='feng_persons'), (SELECT id FROM ".TABLE_PREFIX."object_types WHERE name='company' LIMIT 1), (SELECT id FROM ".TABLE_PREFIX."object_types WHERE name='template_milestone' LIMIT 1),0,1)
		ON DUPLICATE KEY UPDATE dimension_id=dimension_id;
");
}

/**
 * Contact member cache
 *
 */
function core_dimensions_update_7_8() {
	//UPDATE depth for all members
	//update root members
	DB::execute("UPDATE ".TABLE_PREFIX."members SET depth = 1  WHERE parent_member_id = 0;");
	//clean root members
	DB::execute("UPDATE ".TABLE_PREFIX."members SET depth = 2  WHERE parent_member_id != 0 AND depth = 1;");
		
	$members_depth = DB::executeAll("SELECT id FROM ".TABLE_PREFIX."members WHERE parent_member_id =0 ORDER BY id");
	$members_depth = array_flat($members_depth);
	$members_depth = implode(",", $members_depth);
	
	$depth = 2;
	$max_depth = DB::executeOne("SELECT  MAX(depth) AS depth FROM `".TABLE_PREFIX."members`");
			
	//update all depths
	for ($i = $depth; $i <= $max_depth['depth']; $i++) {
		//update members depth
		DB::execute("UPDATE ".TABLE_PREFIX."members SET depth = ".$depth." WHERE parent_member_id  IN (".$members_depth.");");
		
		//Get member from next depth
		$members_depth = DB::executeAll("SELECT id FROM ".TABLE_PREFIX."members WHERE depth= ".$depth." ORDER BY id");
		$members_depth = array_flat($members_depth);
		$members_depth = implode(",", $members_depth);
		
		$depth++;
	}
	//END UPDATE depth for all members

	//Load the contact member cache
	set_time_limit(0);
	ini_set('memory_limit', '512M');
	$users = Contacts::getAllUsers();
		
	$dimensions = Dimensions::findAll();
	$dimensions_ids = array();
	foreach ($dimensions as $dimension) {
		if ($dimension->getDefinesPermissions()) {
			$dimensions_ids[] = $dimension->getId();
		}
	}
	$dimensions_ids = implode(",",$dimensions_ids);
	$root_members = DB::executeAll("SELECT * FROM ".TABLE_PREFIX."members WHERE dimension_id IN (".$dimensions_ids.") AND parent_member_id=0 ORDER BY id");
	foreach ($users as $user) {
		try {
			DB::beginWork();
			foreach ($root_members as $member) {
				ContactMemberCaches::updateContactMemberCache($user, $member['id'], $member['parent_member_id']);
			}
			DB::commit();
		} catch (Exception $e) {
			DB::rollback();
			throw $e;
		}		
	}
	//END Load the contact member cache
}