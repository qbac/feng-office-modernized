<?php
	require_javascript("og/modules/addContactForm.js");
	$genid = gen_id();
	$object = $contact;
	$renderContext = has_context_to_render($contact->manager()->getObjectTypeId());
	if ((!$object->isNew() && $object->isUser()) || array_var($_GET, 'is_user')) {
		$renderContext = false;
	}
	
	$visible_cps = CustomProperties::countVisibleCustomPropertiesByObjectType($object->getObjectTypeId());
	
?>

<form id="<?php echo $genid ?>submit-edit-form" style='height:100%;background-color:white' class="internalForm" action="<?php echo $contact->isNew() ? $contact->getAddUrl() : $contact->getEditUrl() ?>" method="post">
<input id="<?php echo $genid ?>hfIsNewCompany" type="hidden" name="contact[isNewCompany]" value=""/>

<div class="contact">
<div class="coInputHeader">
	<div class="coInputHeaderUpperRow">
	<div class="coInputTitle"><table style="width:535px">
		<tr><td><?php echo $contact->isNew() ? lang('new contact') : lang('edit contact') ?>
		</td><td style="text-align:right"><?php echo submit_button($contact->isNew() ? lang('add contact') : lang('save changes'),'s',array('style'=>'margin-top:0px;margin-left:10px', 'id' => $genid . 'submit1')) ?></td></tr></table>
	</div>
	
	</div>
	<input type="hidden" name="contact[new_contact_from_mail_div_id]" value="<?php echo array_var($contact_data, 'new_contact_from_mail_div_id', '') ?>"/>
	<input type="hidden" name="contact[hf_contacts]" value="<?php echo array_var($contact_data, 'hf_contacts') ?>"/>

	
	<?php $categories = array(); Hook::fire('object_edit_categories', $object, $categories); ?>
	
	<div style="padding-top:5px">	
		<?php foreach ($categories as $category) : ?>
		<a href="#" class="option" <?php if ($category['visible']) echo 'style="font-weight: bold"'; ?> onclick="og.toggleAndBolden('<?php echo $genid . $category['name'] ?>', this)"><?php echo lang($category['name'])?></a>-
		<?php endforeach; ?>	
		<?php if ( $renderContext ) :?>
		<a href="#" class="option" id="<?php echo $genid?>related_to_link" onclick="og.toggleAndBolden('<?php echo $genid ?>add_contact_select_context_div',this)"><?php echo lang('context') ?></a> -
		<?php endif;?>
		 
		<a href="#" class="option <?php echo $visible_cps>0 ? 'bold' : ''?>" onclick="og.toggleAndBolden('<?php echo $genid ?>add_custom_properties_div',this)"><?php echo lang('custom properties') ?></a> - 
		<a href="#" class="option" onclick="og.toggleAndBolden('<?php echo $genid ?>add_subscribers_div',this)"><?php echo lang('object subscribers') ?></a>
		<?php if($object->isNew() || $object->canLinkObject(logged_user())) { ?> - 
			<a href="#" class="option" onclick="og.toggleAndBolden('<?php echo $genid ?>add_linked_objects_div',this)"><?php echo lang('linked objects') ?></a>
		<?php } ?>

	</div>
</div>
<div class="coInputSeparator"></div>

	
<div class="coInputMainBlock">
	<input id="<?php echo $genid?>updated-on-hidden" type="hidden" name="updatedon" value="<?php echo !$contact->isNew() ?  $contact->getUpdatedOn()->getTimestamp() : '' ?>">
	<input id="<?php echo $genid?>merge-changes-hidden" type="hidden" name="merge-changes" value="" >
	<input id="<?php echo $genid?>genid" type="hidden" name="genid" value="<?php echo $genid ?>" >

	<?php foreach ($categories as $category) : ?>
	<div <?php if (!$category['visible']) echo 'style="display:none"' ?> id="<?php echo $genid . $category['name'] ?>">
	<fieldset>
		<legend><?php echo lang($category['name'])?><?php if ($category['required']) echo ' <span class="label_required">*</span>'; ?></legend>
		<?php echo $category['content'] ?>
	</fieldset>
	</div>
	<?php endforeach; ?>
	
	
	<?php if ($renderContext): ?>
	<div id="<?php echo $genid ?>add_contact_select_context_div" style="display:none">
	<fieldset>
		<legend><?php echo lang('context')?></legend>
			<?php 
				$listeners = array('on_selection_change' => 'og.reload_subscribers("'.$genid.'",'.$object->manager()->getObjectTypeId().')');
				if ($contact->isNew()) {
					render_member_selectors($contact->manager()->getObjectTypeId(), $genid, null, array('select_current_context' => true, 'listeners' => $listeners)); 
				} else {
					render_member_selectors($contact->manager()->getObjectTypeId(), $genid, $contact->getMemberIds(), array('listeners' => $listeners)); 
				} 
			?>
	</fieldset>
	</div>
	<?php endif ;?>
		
	
	<div style="margin-left:12px;margin-right:12px;" class="contact_form_container">
		<div class="information-block">
			<div class="main-data-title"><?php echo lang('main data')?></div>
			
			<div class="input-container">
				<?php echo label_tag(lang('first name'), $genid . 'profileFormFirstName') ?>
				<?php echo text_field('contact[first_name]', (isset ($_POST['widget_name'])? $_POST['widget_name']:array_var($contact_data, 'first_name')), 
					array('id' => $genid . 'profileFormFirstName', 'maxlength' => 50)) ?>
			</div>
			<div class="clear"></div>
			
			<div class="input-container">
				<?php echo label_tag(lang('last name'), $genid . 'profileFormSurName') ?>
				<?php echo text_field('contact[surname]', (isset ($_POST['widget_surname'])? $_POST['widget_surname']:array_var($contact_data, 'surname')), 
				array('id' => $genid . 'profileFormSurname',  'maxlength' => 50)) ?>
			</div>
			<div class="clear"></div>
			
			<div class="input-container">
				<?php echo label_tag(lang('email address'), $genid.'profileFormEmail') ?>
				<?php echo text_field('contact[email]', (isset ($_POST['widget_email'])? $_POST['widget_email']:array_var($contact_data, 'email')), 
					array('id' => $genid.'profileFormEmail', 'maxlength' => 100, 'style' => 'width:260px;')) ?>
			</div>
			<div class="clear"></div>
		
			<div>
				<div id="<?php echo $genid ?>existing_company">
					<?php echo label_tag(lang('company'), $genid.'profileFormCompany') ?> 
					<?php echo select_box('contact[company_id]', array(), array('id' => $genid.'profileFormCompany', "class" => "og-edit-contact-select-company", 'onchange' => 'og.companySelectedIndexChanged(\''.$genid . '\')'))?>
					<span class="widget-body loading" id="<?php echo $genid?>profileFormCompany-loading" style="heigth:20px;background-color:transparent;border:0px none;display:none;"></span>
					<a href="#" class="coViewAction ico-add" title="<?php echo lang('add a new company')?>" onclick="og.addNewCompany('<?php echo $genid ?>')"><?php echo lang('add company') . '...' ?></a>
				</div>
				
				<div id="<?php echo $genid?>new_company" style="display:none; padding:6px; margin-top:6px;margin-bottom:6px; background-color:#EEE">
					<div style="float:right;"><a href="#" title="<?php echo lang('cancel')?>" onclick="og.addNewCompany('<?php echo $genid ?>')"><?php echo lang('cancel') ?></a></div>
					
					<div class="input-container">
						<div><?php echo label_tag(lang('new company name')) ?></div>
						<div style="float:left;"><?php echo text_field('company[first_name]', '', array('id' => $genid.'profileFormNewCompanyName', 'onchange' => 'og.checkNewCompanyName("'.$genid .'")')) ?></div>
						<div class="clear"></div>
					</div>
					
					<div class="input-container">
						<div><?php echo label_tag(lang('email address'), $genid.'clientFormEmail') ?></div>
						<div style="float:left;"><?php echo text_field('company[email]', '', array('id' => $genid.'clientFormAssistantNumber')) ?></div>
						<div class="clear"></div>
					</div>
					
					<div class="input-container">
						<div><?php echo label_tag(lang('phone')) ?></div>
			            <div style="float:left;" id="<?php echo $genid?>_comp_phones_container"></div>
			            <div class="clear"></div>
			            <div style="margin:5px 0 10px 200px;">
			            	<a href="#" onclick="og.addNewTelephoneInput('<?php echo $genid?>_comp_phones_container', 'company')" class="coViewAction ico-add"><?php echo lang('add new phone number')?></a>
			            </div>
			        </div>
			        
			        <div class="input-container">
			            <div><?php echo label_tag(lang('address')) ?></div>
			            <div style="float:left;" id="<?php echo $genid?>_comp_addresses_container"></div>
			            <div class="clear"></div>
			            <div style="margin:5px 0 10px 200px;">
			            	<a href="#" onclick="og.addNewAddressInput('<?php echo $genid?>_comp_addresses_container', 'company')" class="coViewAction ico-add"><?php echo lang('add new address') ?></a>
			            </div>
		            </div>
		            
		            <div class="input-container">
			            <div><?php echo label_tag(lang('webpage')) ?></div>
			            <div style="float:left;" id="<?php echo $genid?>_comp_webpages_container"></div>
			            <div class="clear"></div>
			            <div style="margin:5px 0 10px 200px;">
			            	<a href="#" onclick="og.addNewWebpageInput('<?php echo $genid?>_comp_webpages_container', 'company')" class="coViewAction ico-add"><?php echo lang('add new webpage') ?></a>
			            </div>
			        </div>
				</div>
				
				
			</div>
	
			<div class="clear"></div>
	
			<div class="input-container">
				<div><?php echo label_tag(lang('job title'), $genid.'profileFormJobTitle') ?>
				<?php echo text_field('contact[job_title]', array_var($contact_data, 'job_title'), array('id' => $genid.'profileFormJobTitle', 'maxlength' => '40', 'maxlength' => 50)) ?></div>
				<div class="clear"></div>
			</div>
			
			<div class="input-container">
				<div><?php echo label_tag(lang('phone')) ?></div>
	            <div style="float:left;" id="<?php echo $genid?>_phones_container"></div>
	            <div class="clear"></div>
	            <div style="margin:5px 0 10px 200px;">
	            	<a href="#" onclick="og.addNewTelephoneInput('<?php echo $genid?>_phones_container')" class="coViewAction ico-add"><?php echo lang('add new phone number')?></a>
	            </div>
	        </div>
	        
	        <div class="input-container">
				<div><?php echo label_tag(lang('avatar')) ?></div>
	            <div style="float:left;" id="<?php echo $genid?>_avatar_container" class="avatar-container">
	            	<img src="<?php echo $contact->getAvatarUrl() ?>" alt="<?php echo clean($contact->getObjectName()) ?>" id="<?php echo $genid?>_avatar_img"/>
	            </div>
	            <div style="padding:20px 0 0 20px; text-decoration:underline; float:left; display:none;">
		           	<a href="<?php echo $contact->getUpdateAvatarUrl()?>&reload_picture=<?php echo $genid?>_avatar_container" class="internallink coViewAction ico-picture" target=""><?php echo lang('update avatar') ?></a>
				</div>
				
				<div style="padding:20px 0 0 20px; text-decoration:underline; float:left;">
		           	<a href="#" onclick="og.openLink('<?php echo $contact->getUpdatePictureUrl();?>&reload_picture=<?php echo $genid?>_avatar_img<?php echo ($contact->isNew() ? '&new_contact='.$genid.'_picture_file' :'')?>', {caller:'edit_picture'});" 
		           		class="coViewAction ico-picture"><?php echo lang('update avatar') ?></a>
		           	<?php if ($contact->isNew()) { ?>
		           		<input type="hidden" id="<?php echo $genid?>_picture_file" name="contact[picture_file]" value=""/>
		           	<?php }?>
				</div>
				
	            <div class="clear"></div>
			</div>
		</div>
		
		<?php if (!$contact->isNew() && $contact->isUser()) { ?>
		<div style="margin: 5px 0; text-decoration:underline;">
           	<a href="#" onclick="og.toggle('<?php echo $genid?>_user_data');this.style.display='none';" class="coViewAction ico-user"><?php echo lang('edit user data') ?></a>
		</div>
		<div id="<?php echo $genid?>_user_data" class="user-data" style="display:none;">
			<div class="information-block">
            	<div class="user-data-title"><?php echo lang('user data')?></div>
            	<div class="input-container">
	            	<div id="<?php echo $genid ?>update_profile_timezone">
						<label><?php echo lang('auto detect user timezone') ?></label>
						<div id="<?php echo $genid?>detectTimeZone" style="vertical-align:middle;">
						<?php
							$now = DateTimeValueLib::now(); 
							$on_autodetect_click = 'og.getTimezoneFromBrowser(new Date('.$now->getYear().','.($now->getMonth() - 1).','.$now->getDay().','.$now->getHour().','.$now->getMinute().','.$now->getSecond().'), \''.$genid.'\');';
						?>
						
							<div style="float:left; padding-top: 5px; margin: 0 15px 0 0;">
							<?php echo yes_no_widget('contact[autodetect_time_zone]', $genid.'userFormAutoDetectTimezone', user_config_option('autodetect_time_zone', null, $contact->getId()), 
								lang('yes'), lang('no'), null, array('onclick' => "og.showSelectTimezone('$genid');$on_autodetect_click")) ?>
							</div>
							<div id="<?php echo $genid?>selecttzdiv" <?php if (user_config_option('autodetect_time_zone', null, $contact->getId())) echo 'style="float:left; display:none; "'; ?>>
								<?php echo select_timezone_widget('contact[timezone]', array_var($contact_data, 'timezone'), array('id' => $genid.'userFormTimezone', 'class' => 'long')) ?>
							</div>
							
						</div>
						<div class="clear"></div>
					</div>
				</div>
				
				<div class="input-container">
					<?php echo label_tag(lang('username'), $genid . 'profileFormUsername') ?>
	      			<?php echo text_field('user[username]', array_var($contact_data, 'username'), array('id' => $genid . 'profileFormUsername')) ?>
				</div>
				
				<div style="margin: 5px 0; text-decoration:underline;">
		           	<a href="#" onclick="og.openLink('<?php echo $contact->getUpdatePermissionsUrl()?>');" class="coViewAction ico-permissions"><?php echo lang('edit permissions') ?></a>
				</div>
				
            </div>
		</div>
		<?php } ?>
		
		<?php if ($contact->isNew() || $isEdit){ ?>
		<div class="information-block user-data">
			<div class="user-data-title"><?php echo lang('user data')?></div>
		<?php 
				tpl_assign('contact_mail', $contact_mail);
				tpl_assign('orig_genid', $genid);
				tpl_assign('new_contact', $object->isNew());
				$this->includeTemplate(get_template_path("add_contact/access_data_edit","contact"));
		?></div>
		<?php } ?>
            
		<div style="margin: 5px 0; text-decoration:underline;">
           	<a href="#" onclick="og.toggle('<?php echo $genid?>_additional_data');this.style.display='none';" class="coViewAction ico-edit"><?php echo lang('edit all person data') ?></a>
		</div>
			
		<div id="<?php echo $genid?>_additional_data" class="additional-data" style="display:none;">
			<div class="information-block">
				<div class="additional-data-title"><?php echo lang('additional data')?></div>
				<div class="input-container">
					<?php echo label_tag(lang('birthday'), $genid.'profileFormBirthday')?>
					<span style="float:left;"><?php echo pick_date_widget2('contact[birthday]', array_var($contact_data, 'birthday'), $genid, 265) ?></span>
				</div>
				<div class="clear"></div>
				
				<div class="input-container">
					<?php echo label_tag(lang('department'), $genid.'profileFormDepartment') ?>
					<?php echo text_field('contact[department]', array_var($contact_data, 'department'), array('id' => $genid.'profileFormDepartment', 'maxlength' => 50)) ?>
				</div>
				<div class="clear"></div>
				
				<div class="input-container">
		            <div><?php echo label_tag(lang('email address')) ?></div>
		            <div style="float:left;" id="<?php echo $genid?>_emails_container"></div>
		            <div class="clear"></div>
		            <div style="margin:5px 0 10px 200px;">
		            	<a href="#" onclick="og.addNewEmailInput('<?php echo $genid?>_emails_container')" class="coViewAction ico-add"><?php echo lang('add new email address') ?></a>
		            </div>
		        </div>
	            
	            <div style="display:none;"><?php echo select_country_widget('country', '', array('id'=>'template_select_country'));?></div>
	            <div class="input-container">
		            <div><?php echo label_tag(lang('address')) ?></div>
		            <div style="float:left;" id="<?php echo $genid?>_addresses_container"></div>
		            <div class="clear"></div>
		            <div style="margin:5px 0 10px 200px;">
		            	<a href="#" onclick="og.addNewAddressInput('<?php echo $genid?>_addresses_container')" class="coViewAction ico-add"><?php echo lang('add new address') ?></a>
		            </div>
	            </div>
	            
	            <div class="input-container">
		            <div><?php echo label_tag(lang('webpage')) ?></div>
		            <div style="float:left;" id="<?php echo $genid?>_webpages_container"></div>
		            <div class="clear"></div>
		            <div style="margin:5px 0 10px 200px;">
		            	<a href="#" onclick="og.addNewWebpageInput('<?php echo $genid?>_webpages_container')" class="coViewAction ico-add"><?php echo lang('add new webpage') ?></a>
		            </div>
		        </div>
	
	            <div class="input-container">
					<div><?php echo label_tag(lang('instant messengers')) ?></div>
					<div style="float:left;" class="im-container">
						<table class="blank">
							<tr>
								<th colspan="2"><?php echo lang('im service') ?></th>
								<th><?php echo lang('value') ?></th>
								<th><?php echo lang('primary im service') ?></th>
							</tr>
							<?php foreach($im_types as $im_type) { ?>
							<tr>
								<td style="vertical-align: middle"><img src="<?php echo $im_type->getIconUrl() ?>" alt="<?php echo $im_type->getName() ?> icon" /></td>
								<td style="vertical-align: middle"><span style="padding:0 5px;"><?php echo $im_type->getName() ?></span></td>
								<td style="vertical-align: middle"><?php echo text_field('contact[im_' . $im_type->getId() . ']', array_var($contact_data, 'im_' . $im_type->getId()), array('id' => $genid.'profileFormIm' . $im_type->getId())) ?></td>
								<td style="vertical-align: middle;text-align: center;"><?php echo radio_field('contact[default_im]', array_var($contact_data, 'default_im') == $im_type->getId(), array('value' => $im_type->getId())) ?></td>
							</tr>
							<?php } // foreach ?>
						</table>
					</div>
				</div>
				<div class="clear"></div>
			
				<div class="input-container">
					<div id="<?php echo $genid ?>add_contact_notes">
						<?php echo label_tag(lang('notes'), $genid.'profileFormNotes') ?>
						<div style="float:left;width:600px;" class="notes-container">
							<?php echo textarea_field('contact[comments]', array_var($contact_data, 'comments'), array('id' => $genid.'profileFormNotes', 'style' => 'width: 100%;')) ?>
						</div>
					</div>
				</div>
				<div class="clear"></div>
			</div>
		
		</div>
	</div>
		
		
	
	
	
	
	<div id='<?php echo $genid ?>add_custom_properties_div' style="<?php echo ($visible_cps > 0 ? "" : "display:none") ?>">
		<fieldset>
			<legend><?php echo lang('custom properties') ?></legend>
			<?php echo render_object_custom_properties($object, false) ?>
			<?php echo render_add_custom_properties($object); ?>
		</fieldset>
	</div>
	
	<div id="<?php echo $genid ?>add_subscribers_div" style="display:none">
		<fieldset>
		<legend><?php echo lang('object subscribers') ?></legend>
		<?php $subscriber_ids = array();
			if (!$object->isNew()) {
				$subscriber_ids = $object->getSubscriberIds();
			} else {
				$subscriber_ids[] = logged_user()->getId();
			} 
		?><input type="hidden" id="<?php echo $genid ?>subscribers_ids_hidden" value="<?php echo implode(',',$subscriber_ids)?>"/>
		<div id="<?php echo $genid ?>add_subscribers_content">
		<?php //echo render_add_subscribers($object, $genid); ?>
		</div>
		</fieldset>
	</div>
	
	
	<?php if($object->isNew() || $object->canLinkObject(logged_user())) : ?>
		<div style="display:none" id="<?php echo $genid ?>add_linked_objects_div">
		<fieldset>
			<legend><?php echo lang('linked objects') ?></legend>
			<?php echo render_object_link_form($object) ?>
		</fieldset>	
		</div>
	<?php endif; ?>
	
	
  	<?php echo submit_button($contact->isNew() ? lang('add contact') : lang('save changes'),'s', array('id' => $genid . 'submit2')) ?>

	<script>

		var is_new_contact = <?php echo $object->isNew() ? 'true' : 'false'?>;
		$(document).ready(function() {
		
			og.load_company_combo("<?php echo $genid?>profileFormCompany", '<?php echo (isset ($_POST['widget_company'])? $_POST['widget_company']:array_var($contact_data, 'company_id', '0')) ?>');

			
			og.telephoneCount = 0;
			og.telephone_types = Ext.util.JSON.decode('<?php echo json_encode($all_telephone_types)?>');

			og.addressCount = 0;
			og.address_types = Ext.util.JSON.decode('<?php echo json_encode($all_address_types)?>');

			og.webpageCount = 0;
			og.webpage_types = Ext.util.JSON.decode('<?php echo json_encode($all_webpage_types)?>');

			og.emailCount = 0;
			og.email_types = Ext.util.JSON.decode('<?php echo json_encode($all_email_types)?>');

			if (!is_new_contact) {
			<?php foreach (array_var($contact_data, 'all_phones') as $phone) { ?>
				og.addNewTelephoneInput('<?php echo $genid?>_phones_container', 'contact', '<?php echo $phone->getTelephoneTypeId()?>', '<?php echo $phone->getNumber()?>', '<?php echo $phone->getName()?>', '<?php echo $phone->getId()?>');
			<?php } ?>

			<?php foreach (array_var($contact_data, 'all_addresses') as $address) { ?>
				og.addNewAddressInput('<?php echo $genid?>_addresses_container', 'contact', '<?php echo $address->getAddressTypeId()?>', {
					street: '<?php echo str_replace("\n", " ", $address->getStreet())?>',
					city: '<?php echo $address->getCity()?>',
					state: '<?php echo $address->getState()?>',
					zip_code: '<?php echo $address->getZipCode()?>',
					country: '<?php echo $address->getCountry()?>',
					id: '<?php echo $address->getId()?>'
				});
			<?php } ?>
			
			<?php foreach (array_var($contact_data, 'all_webpages') as $webpage) { ?>
				og.addNewWebpageInput('<?php echo $genid?>_webpages_container', 'contact', '<?php echo $webpage->getWebTypeId()?>', '<?php echo $webpage->getUrl()?>', '<?php echo $webpage->getId()?>');
			<?php } ?>

			<?php foreach (array_var($contact_data, 'all_emails') as $email) { ?>
				og.addNewEmailInput('<?php echo $genid?>_emails_container', 'contact', '<?php echo $email->getEmailTypeId()?>', '<?php echo $email->getEmailAddress()?>', '<?php echo $email->getId()?>');
			<?php } ?>
			}

			for (var i=0; i<og.telephone_types.length; i++) {
				if (og.telephone_types[i].code == 'work') def_phone_type = og.telephone_types[i].id;
			}
			for (var i=0; i<og.address_types.length; i++) {
				if (og.address_types[i].code == 'work') def_address_type = og.address_types[i].id;
			}
			for (var i=0; i<og.webpage_types.length; i++) {
				if (og.webpage_types[i].code == 'work') def_web_type = og.webpage_types[i].id;
			}
			for (var i=0; i<og.email_types.length; i++) {
				if (og.email_types[i].code == 'work') def_email_type = og.email_types[i].id;
			}
			
			<?php if (count(array_var($contact_data, 'all_phones')) == 0) { ?>
				og.addNewTelephoneInput('<?php echo $genid?>_phones_container', 'contact', def_phone_type);
			<?php } ?>
			<?php if (count(array_var($contact_data, 'all_addresses')) == 0) { ?>
				og.addNewAddressInput('<?php echo $genid?>_addresses_container', 'contact', def_address_type);
			<?php } ?>
			<?php if (count(array_var($contact_data, 'all_webpages')) == 0) { ?>
				og.addNewWebpageInput('<?php echo $genid?>_webpages_container', 'contact', def_web_type);
			<?php } ?>
			<?php if (count(array_var($contact_data, 'all_emails')) == 0) { ?>
				og.addNewEmailInput('<?php echo $genid?>_emails_container', 'contact', def_email_type);
			<?php } ?>

			og.addNewTelephoneInput('<?php echo $genid?>_comp_phones_container', 'company', def_phone_type);
			og.addNewAddressInput('<?php echo $genid?>_comp_addresses_container', 'company', def_address_type);
			og.addNewWebpageInput('<?php echo $genid?>_comp_webpages_container', 'company', def_web_type);
			
			<?php if(isset ($_POST['widget_is_user'])){ ?>
				$('input[name*="contact[user][create-user]"]').prop("checked",true);
				$(".user-data").show();
			<?php } ?>
			<?php if(isset ($_POST['widget_user_type'])){ ?>
				$('[name="contact[user][type]"]').val(<?php echo $_POST['widget_user_type'] ?>);
			<?php } ?>

			og.checkEmailAddress("#<?php echo $genid ?>profileFormEmail",'<?php echo $contact->getId();?>','<?php echo $genid ?>');
			
			Ext.get('<?php echo $genid ?>profileFormFirstName').focus();
			
		});



		
	</script>
</div>
</div>
</form>