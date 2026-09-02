<?php 
	require_javascript('og/modules/addMessageForm.js');
	$genid = gen_id();
	$object = $company;
	if($company->isNew()) { 
		$form_action = get_url('contact', 'add_company'); 
	} else {
		$form_action = $company->getEditUrl();
	}
	$renderContext = has_context_to_render($company->manager()->getObjectTypeId());
	$visible_cps = CustomProperties::countVisibleCustomPropertiesByObjectType($object->getObjectTypeId());	
?>
<form style="height:100%;background-color:white" class="internalForm" action="<?php echo $form_action ?>" method="post">


<div class="adminAddCompany">
  <div class="adminHeader">
  	<div class="adminHeaderUpperRow">
  		<div class="adminTitle"><table style="width:535px"><tr><td>
  			<?php echo $company->isNew() ? lang('new company') : lang('edit company') ?>
  		</td><td style="text-align:right">
  			<?php echo submit_button($company->isNew() ? lang('add company') : lang('save changes'), 's', array('style'=>'margin-top:0px;margin-left:10px')) ?>
  		</td></tr></table></div>
  	</div>
  	
  
  
  	<?php $categories = array(); Hook::fire('object_edit_categories', $object, $categories); ?>
  	
  	<div style="padding-top:5px">
	  	<?php if ( $renderContext ) :?>
			<a href="#" class="option" onclick="og.toggleAndBolden('<?php echo $genid ?>add_company_select_context_div',this)"><?php echo lang('context') ?></a> -
		<?php endif; ?>
			<a href="#" class="option <?php echo $visible_cps>0 ? 'bold' : ''?>" onclick="og.toggleAndBolden('<?php echo $genid ?>add_custom_properties_div',this)"><?php echo lang('custom properties') ?></a> -
			<a href="#" class="option" onclick="og.toggleAndBolden('<?php echo $genid ?>add_subscribers_div',this)"><?php echo lang('object subscribers') ?></a>
		<?php if($object->isNew() || $object->canLinkObject(logged_user())) { ?> - 
			<a href="#" class="option" onclick="og.toggleAndBolden('<?php echo $genid ?>add_linked_objects_div',this)"><?php echo lang('linked objects') ?></a>
		<?php } ?>
		<?php foreach ($categories as $category) { ?>
			- <a href="#" class="option" <?php if ($category['visible']) echo 'style="font-weight: bold"'; ?> onclick="og.toggleAndBolden('<?php echo $genid . $category['name'] ?>', this)"><?php echo lang($category['name'])?></a>
		<?php } ?>
	</div>
  </div>
  <div class="adminSeparator"></div>
  <div class="adminMainBlock">
	
	<?php if ( $renderContext ) :?>
		<div id="<?php echo $genid ?>add_company_select_context_div" style="display:none">
			<fieldset>
				<legend><?php echo lang('context')?></legend>
				<?php 
				$listeners = array('on_selection_change' => 'og.reload_subscribers("'.$genid.'",'.$object->manager()->getObjectTypeId().')');
				if ($company->isNew()) {
					render_member_selectors($company->manager()->getObjectTypeId(), $genid, null, array('select_current_context' => true, 'listeners' => $listeners));
				} else {
					render_member_selectors($company->manager()->getObjectTypeId(), $genid, $company->getMemberIds(), array('listeners' => $listeners)); 
				} ?>
			</fieldset>
		</div>
	<?php endif ;?>	
	
	<div style="margin-left:12px;margin-right:12px;" class="contact_form_container">
		<div class="information-block">
			<div class="main-data-title"><?php echo lang('main data')?></div>
			
			<div class="input-container">
				<?php echo label_tag(lang('name'), $genid . 'clientFormName') ?>
				<?php echo text_field('company[first_name]',  array_var($company_data, 'first_name'), array('class' => 'title', 'id' => $genid . 'clientFormName')) ?>
			</div>
			<div class="clear"></div>
			
			<div class="input-container">
				<?php echo label_tag(lang('email address'), $genid.'clientFormEmail') ?>
				<?php echo text_field('company[email]', array_var($company_data, 'email'), array('id' => $genid.'clientFormEmail', 'style' => 'width: 260px;')) ?>
			</div>
			<div class="clear"></div>
			
			<div class="input-container">
				<div><?php echo label_tag(lang('phone')) ?></div>
	            <div style="float:left;" id="<?php echo $genid?>_phones_container"></div>
	            <div class="clear"></div>
	            <div style="margin:5px 0 10px 200px;">
	            	<a href="#" onclick="og.addNewTelephoneInput('<?php echo $genid?>_phones_container', 'company')" class="coViewAction ico-add"><?php echo lang('add new phone number')?></a>
	            </div>
	        </div>
	        
	        <div style="display:none;"><?php echo select_country_widget('country', '', array('id'=>'template_select_country'));?></div>
            <div class="input-container">
	            <div><?php echo label_tag(lang('address')) ?></div>
	            <div style="float:left;" id="<?php echo $genid?>_addresses_container"></div>
	            <div class="clear"></div>
	            <div style="margin:5px 0 10px 200px;">
	            	<a href="#" onclick="og.addNewAddressInput('<?php echo $genid?>_addresses_container', 'company')" class="coViewAction ico-add"><?php echo lang('add new address') ?></a>
	            </div>
            </div>
            
            <div class="input-container">
	            <div><?php echo label_tag(lang('webpage')) ?></div>
	            <div style="float:left;" id="<?php echo $genid?>_webpages_container"></div>
	            <div class="clear"></div>
	            <div style="margin:5px 0 10px 200px;">
	            	<a href="#" onclick="og.addNewWebpageInput('<?php echo $genid?>_webpages_container', 'company')" class="coViewAction ico-add"><?php echo lang('add new webpage') ?></a>
	            </div>
	        </div>
				
			<div class="input-container">
	            <div><?php echo label_tag(lang('other email addresses')) ?></div>
	            <div style="float:left;" id="<?php echo $genid?>_emails_container"></div>
	            <div class="clear"></div>
	            <div style="margin:5px 0 10px 200px;">
	            	<a href="#" onclick="og.addNewEmailInput('<?php echo $genid?>_emails_container', 'company')" class="coViewAction ico-add"><?php echo lang('add new email address') ?></a>
	            </div>
	        </div>
	        
	        <div class="input-container">
				<div><?php echo label_tag(lang('logo')) ?></div>
	            <div style="float:left;" id="<?php echo $genid?>_avatar_container" class="avatar-container">
	            	<img src="<?php echo $company->getPictureUrl() ?>" alt="<?php echo clean($company->getObjectName()) ?>" id="<?php echo $genid?>_avatar_img"/>
	            </div>
	            <div style="padding:20px 0 0 20px; text-decoration:underline; float:left; display:none;">
		           	<a href="<?php echo $company->getUpdatePictureUrl()?>&reload_picture=<?php echo $genid?>_avatar_container" class="internallink coViewAction ico-picture" target=""><?php echo lang('update logo') ?></a>
				</div>
				
				<div style="padding:20px 0 0 20px; text-decoration:underline; float:left;">
		           	<a href="#" onclick="og.openLink('<?php echo $company->getUpdatePictureUrl();?>&reload_picture=<?php echo $genid?>_avatar_img<?php echo ($company->isNew() ? '&new_contact='.$genid.'_picture_file' :'')?>', {caller:'edit_picture'});" 
		           		class="coViewAction ico-picture"><?php echo lang('update logo') ?></a>
		           	<?php if ($company->isNew()) { ?>
		           		<input type="hidden" id="<?php echo $genid?>_picture_file" name="company[picture_file]" value=""/>
		           	<?php }?>
				</div>
				
	            <div class="clear"></div>
			</div>
			
			<div class="input-container">
				<?php echo label_tag(lang('timezone'), 'clientFormTimezone', false)?>
    			<?php echo select_timezone_widget('company[timezone]', array_var($company_data, 'timezone'), array('id' => 'clientFormTimezone', 'class' => 'long')) ?>
    			<div class="clear"></div>
			</div>
	        
	        <div class="input-container">
		      <?php echo label_tag(lang('notes'), $genid.'profileFormNotes') ?>
		      <div style="float:left;width:600px;" class="notes-container">
		      <?php echo textarea_field('company[comments]', array_var($company_data, 'comments'), array('id' => $genid.'profileFormNotes', 'style' => 'width: 100%;')) ?>
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
	
	

	<?php if($object->isNew() || $object->canLinkObject(logged_user())) { ?>
	<div style="display:none" id="<?php echo $genid ?>add_linked_objects_div">
	<fieldset>
		<legend><?php echo lang('linked objects') ?></legend>
		<?php echo render_object_link_form($object) ?>
	</fieldset>	
	</div>
	<?php } // if ?>
		
	
  <?php foreach ($categories as $category) { ?>
	<div <?php if (!$category['visible']) echo 'style="display:none"' ?> id="<?php echo $genid . $category['name'] ?>">
	<fieldset>
		<legend><?php echo lang($category['name'])?><?php if ($category['required']) echo ' <span class="label_required">*</span>'; ?></legend>
		<?php echo $category['content'] ?>
	</fieldset>
	</div>
	<?php } ?>
	
	
	
	
	
  
<?php 
	if(!$company->isNew() && $company->isOwnerCompany()) { 
		echo submit_button(lang('save changes'));
	} else {
		echo submit_button($company->isNew() ? lang('add company') : lang('save changes'));
	}
?>
</div>
</div>
</form>

<script>
var is_new_contact = <?php echo $object->isNew() ? 'true' : 'false'?>;
$(document).ready(function() {

	og.telephoneCount = 0;
	og.telephone_types = Ext.util.JSON.decode('<?php echo json_encode($all_telephone_types)?>');

	og.addressCount = 0;
	og.address_types = Ext.util.JSON.decode('<?php echo json_encode($all_address_types)?>');

	og.webpageCount = 0;
	og.webpage_types = Ext.util.JSON.decode('<?php echo json_encode($all_webpage_types)?>');

	og.emailCount = 0;
	og.email_types = Ext.util.JSON.decode('<?php echo json_encode($all_email_types)?>');

	if (!is_new_contact) {
	<?php foreach ($company_data['all_phones'] as $phone) { ?>
		og.addNewTelephoneInput('<?php echo $genid?>_phones_container', 'company', '<?php echo $phone->getTelephoneTypeId()?>', '<?php echo $phone->getNumber()?>', '<?php echo $phone->getName()?>', '<?php echo $phone->getId()?>');
	<?php } ?>

	<?php foreach ($company_data['all_addresses'] as $address) { ?>
		og.addNewAddressInput('<?php echo $genid?>_addresses_container', 'company', '<?php echo $address->getAddressTypeId()?>', {
			street: '<?php echo $address->getStreet()?>',
			city: '<?php echo $address->getCity()?>',
			state: '<?php echo $address->getState()?>',
			zip_code: '<?php echo $address->getZipCode()?>',
			country: '<?php echo $address->getCountry()?>',
			id: '<?php echo $address->getId()?>'
		});
	<?php } ?>
	
	<?php foreach ($company_data['all_webpages'] as $webpage) { ?>
		og.addNewWebpageInput('<?php echo $genid?>_webpages_container', 'company', '<?php echo $webpage->getWebTypeId()?>', '<?php echo $webpage->getUrl()?>', '<?php echo $webpage->getId()?>');
	<?php } ?>

	<?php foreach (array_var($company_data, 'all_emails') as $email) { ?>
		og.addNewEmailInput('<?php echo $genid?>_emails_container', 'company', '<?php echo $email->getEmailTypeId()?>', '<?php echo $email->getEmailAddress()?>', '<?php echo $email->getId()?>');
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
	
	<?php if (count(array_var($company_data, 'all_phones')) == 0) { ?>
		og.addNewTelephoneInput('<?php echo $genid?>_phones_container', 'company', def_phone_type);
	<?php } ?>
	<?php if (count(array_var($company_data, 'all_addresses')) == 0) { ?>
		og.addNewAddressInput('<?php echo $genid?>_addresses_container', 'company', def_address_type);
	<?php } ?>
	<?php if (count(array_var($company_data, 'all_webpages')) == 0) { ?>
		og.addNewWebpageInput('<?php echo $genid?>_webpages_container', 'company', def_web_type);
	<?php } ?>
	<?php if (count(array_var($company_data, 'all_emails')) == 0) { ?>
		og.addNewEmailInput('<?php echo $genid?>_emails_container', 'company', def_email_type);
	<?php } ?>
	
	Ext.get('<?php echo $genid ?>clientFormName').focus();
});
</script>
