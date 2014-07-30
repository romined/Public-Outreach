var current_section; //tracks current display div

$(document).ready(function() {
	$('.edit_button, #status_next_button, .contact_next_button, .back_button, .formpart_next_button, #submit_button').button();
	
	$('.edit_button').click(function() {
		display_section(this.id.slice(0, -5));
	});
	
	$('#date_business_founded, .project_completion_date').datepicker({
		changeYear: true,
		changeMonth: true,
		showMonthAfterYear: true,
		yearRange: '-100:+10'
	});
	
	$('#certification_expiration_date').datepicker({
		changeYear: true,
		changeMonth: true,
		dateFormat: 'mm/dd/yyyy',
		showMonthAfterYear: true,
		yearRange: '-10:+50'
	});
	
	$('#project_experience_wrapper').accordion({heightStyle: "content"});
	
	$('#organization_name').watermark('Ex: Acme Inc.');
	$('#certification_number').watermark('Certification Number');
	$('#other_license_type').watermark('Other license type');
	$('#other_service_type').watermark('Other service type');

	$('.name_field').watermark('Ex: John Smith');
	$('.phone_field').watermark('Ex: (555) 555-5556');
	$('.email_field').watermark('Ex: john@example.com');
	$('.monetary_field').watermark('$1234.56');
	$('.date_field').watermark('mm/dd/yyyy');
	$('.project_name').watermark('Ex: Viability Study of ...');
	$('.project_type').watermark('Ex: Study');
	
	$('#review_edit').button('disable');
	
	$('#status_next_button').click(function() {
		var remaining_steps = $('.status_checkbox').not('.complete');
		
		if (remaining_steps) {
			display_section(remaining_steps[0].id.slice(0, -7));
		} else {
			//This shouldn't occur.
			alert("An error occurred, please reload this page and try your submission again.");
		}
	});
	
	$('.formpart_next_button').click(function() {
		//Locate the form id
		var form_id = $(this).closest('form').prop('id');
		
		//Ensure correct input
		var valid = validate(form_id);
		var review_elt = $('#review_' + form_id);
		if (valid) {
			if (form_id == 'project_experience') {
				var project_count = 0;
				$('.project_name').each(function(){
					//check name, which is required if input.
					if ($(this).val())
						project_count++;
				});
				
				review_elt.text(project_count == 0 ? 'Skipped' : project_count);
			} else if (form_id == 'organization_contact_info') {
				review_elt.text($('ul#organization_contact_info_address_list li').length);
			}else {
				review_elt.text('Complete');
			}
			//Updates status screen
			finish_step();
			
			$('#' + form_id).fadeOut('slow', function() {
				$('#submission_status').fadeIn('slow');
			});
		} else {
			scroll_to_first_error();
		}
	});
	
	$('.back_button').click(function() {
		remove_all_errors()
		$(this).closest('form').fadeOut('slow', function() {
			$('#submission_status').fadeIn('slow');
		});
	});
	
	$('.business_other').change(function() {
		var elt = $('#business_other_name').parent();
		if ($('#business_other_radio_yes').prop('checked')) {
			elt.slideDown('slow');
		} else {
			elt.slideUp('slow');
		}
	});

	$('.preexisting_business').change(function() {
		var elt = $('#preexisting_business_name').parent();
		if ($('#preexisting_business_radio_yes').prop('checked')) {
			elt.slideDown('slow');
		} else {
			elt.slideUp('slow');
		}
	});
	
	$('.excess_liability_choice').change(function() {
		var elt = $('#excess_liability').parent();
		if ($('#excess_liability_choice_radio_yes').prop('checked')) {
			elt.slideDown('slow');
		} else {
			elt.slideUp('slow');
		}
	});
	
	$('#submit_button').click(function() {
		var buttons = $(this).add($(this).prev());
		buttons.prop('disabled', true); //disable submit, back button
		var dlg = $('<p>Uploading Information... <img src="/images/ajax.gif" class="ajax small" /></p>').dialog({
			width: 500,
			title: 'Uploading Vendor Information',
			modal: true,
			closeOnEscape: false,
			dialogClass: 'dialog_no_close'
		});
		$.ajax({
			type: "POST",
			url: "add.php",
			data: $('form').serialize(),
			dataType: 'json',
			success: function(data) {
				console.log(data);
				if (data.error) {
					dlg.text("An error occurred: " + data.error);
				} else {
					dlg.text('Complete! Your vendor information has been captured.');
				}
				dlg.dialog({
					buttons: {
						'Ok' : function() {
							window.location.replace('/?reason=1');
						}
					}
				});
			},
			fail: function(data) {
				alert('A problem occurred while uploading your vendor information. Please try again.');
				dlg.dialog('close');
				buttons.prop('disabled', false);
			}
		});
	});
	
	current_section = '';
	finish_step(); //Sets progress to 0, sets first item as current.
});

//Hide status, show section on section name
function display_section(display_section) {
	current_section = display_section;
	if (display_section == 'add_contacts') {
		display_section = 'contact_form_1';
	}
	$('#submission_status').fadeOut('slow', function() {
		$('#' + display_section).fadeIn('slow');
	});
}

function update_progress(value) {
	$('#progress_bar').progressbar({value:value});
	$('#progress_text').text(value + '% Complete');
}

//Mark status as complete, update percentage bar
function finish_step() {
	if (current_section) {
		$('#' + current_section + '_row, #' + current_section + '_status').removeClass('current incomplete error').addClass('complete');
	}
	
	var remaining_rows = $('#steps_table tr.checklist_row').not('.complete');
	var steps_completed = 6-remaining_rows.length;
	var percentage = steps_completed / 6;
	percentage *= 100;
	update_progress(Math.floor(percentage));

	var enable_review = (steps_completed == 5);
	$('#review_edit').button(enable_review ? 'enable' : 'disable');
	if (enable_review) {
		$('#review_row').removeClass('disabled');
	} else {
		$('#review_row').addClass('disabled');
	}
	
	var next_row = remaining_rows.eq(0);
	if (!next_row.hasClass('error')) {
		//If the next row to be run does not have an error, then make it current.
		next_row.addClass('current');
		next_row.find('.status_checkbox').addClass('current');
	}
}

//Enforce client-side validity constraints on provided form ID
function validate(form_id) {
	//contact forms and organization_contact_info are self validating.
	var valid = true;
	switch (form_id) {
	case 'organization_contact_info':
		var address_list = $('#organization_contact_info_address_list');
		var phone_list = $('#organization_contact_info .active_phones ul');
		var email_list = $('#organization_contact_info .active_email ul');

		if (address_list.children().not('.placeholder').length == 0) {
			valid = add_error('form_errors', 'Address required.', 0, 'address_error');
		}
		if (phone_list.children().not('.placeholder').length == 0) {
			valid = add_error('form_errors', 'Phone required.', 0, 'phone_error');
		}
		if (email_list.children().not('.placeholder').length == 0) {
			valid = add_error('form_errors', 'Email required.', 0, 'email_error');
		}

		break;
	case 'company_info':
		var organization_name_elt = $('#organization_name');
		var company_owner_name_elt = $('#company_owner_name');
		var date_business_founded_elt = $('#date_business_founded');
		var type_business_structure_elt = $('#type_business_structure');
		var certification_expiration_date_elt = $('#certification_expiration_date'); //cert info not required, only validate date when provided
		var maximum_bonding_capacity_elt = $('#maximum_bonding_capacity');
		var remaining_uncommitted_bond_capacity_elt = $('#remaining_uncommitted_bond_capacity');
		
		var organization_name_val = organization_name_elt.val().trim();
		var company_owner_name_val = company_owner_name_elt.val().trim();
		var date_business_founded_val = date_business_founded_elt.val();
		var type_business_structure_val = type_business_structure_elt.val().trim();
		var certification_expiration_date_val = certification_expiration_date_elt.val();
		var maximum_bonding_capacity_val = maximum_bonding_capacity_elt.val();
		var remaining_uncommitted_bond_capacity_val = remaining_uncommitted_bond_capacity_elt.val();
		
		if (!organization_name_val) {
			valid = add_error(organization_name_elt, 'Organization name required.');
		}
		
		if (!company_owner_name_val) {
			valid = add_error(company_owner_name_elt, 'Owner name required.');
		}
		
		if (!date_valid(date_business_founded_val)) {
			valid = add_error(date_business_founded_elt, 'Valid date founded required.');
		}
		
		if (!type_business_structure_val) {
			valid = add_error(type_business_structure_elt, 'Type of business required.');
		}
		
		if (certification_expiration_date_val.length > 0 && !date_valid(certification_expiration_date_val)) {
			valid = add_error(certification_expiration_date_elt, 'Certification date invalid.');
		}
		
		if (maximum_bonding_capacity_val.length > 0 && !currency_valid(maximum_bonding_capacity_val)) {
			valid = add_error(maximum_bonding_capacity_elt, 'Currency value invalid.');
		}
		
		if (remaining_uncommitted_bond_capacity_val.length > 0 && !currency_valid(remaining_uncommitted_bond_capacity_val)) {
			valid = add_error(remaining_uncommitted_bond_capacity_elt, 'Currency value invalid.');
		}
		
		break;
	case 'insurance_info':
		var cgl_aggregate_limit_elt = $('#cgl_aggregate_limit');
		var wc_el_aggregate_limit_elt = $('#wc_el_aggregate_limit');
		var pl_aggregate_limit_elt = $('#pl_aggregate_limit');
		var auto_aggregate_limit_elt = $('#auto_aggregate_limit');
		var excess_liability_elt = $('#excess_liability');

		var cgl_aggregate_limit_val = cgl_aggregate_limit_elt.val();
		var wc_el_aggregate_limit_val = wc_el_aggregate_limit_elt.val();
		var pl_aggregate_limit_val = pl_aggregate_limit_elt.val();
		var auto_aggregate_limit_val = auto_aggregate_limit_elt.val();
		var excess_liability_val = excess_liability_elt.val();
		
		var msg = 'Invalid currency';
		if (!currency_valid(cgl_aggregate_limit_val)) {
			valid = add_error(cgl_aggregate_limit_elt, msg);
		}
		if (!currency_valid(wc_el_aggregate_limit_val)) {
			valid = add_error(wc_el_aggregate_limit_elt, msg);
		}
		if (!currency_valid(pl_aggregate_limit_val)) {
			valid = add_error(pl_aggregate_limit_elt, msg);
		}
		if (!currency_valid(auto_aggregate_limit_val)) {
			valid = add_error(auto_aggregate_limit_elt, msg);
		}
		
		indicated_excess_liability = $('#excess_liability_choice_radio_yes').prop('checked');
		if (indicated_excess_liability) {
			if (!currency_valid(excess_liability_val)) {
				valid = add_error(excess_liability_elt, msg);
			}
		}
		
		all_input = '';
		$('#insurance_info input[type=text]').each(function() {
			var val = $(this).val();
			if (val === undefined) val = '';
			all_input += val.trim();
		});
		if (valid && all_input === '') {
			//User didn't input anything
			valid = false; //Prevents continuation
			$('<p>You have not entered any insurance information.</p>').dialog({
				title: 'No Input',
				modal: true,
				width: 500,
				buttons: {
					"Go Back": function() {
						$(this).dialog('close');
					},
					"Skip This Section": function() {
						$(this).dialog('close');
						
						finish_step();
						
						$('#review_insurance_info').text('Skipped');
						$('#' + form_id).fadeOut('slow', function() {
							$('#submission_status').fadeIn('slow');
						});						
					}
				}
			});
		}
		
		break;
	case 'project_experience':
		//Normally this isn't necessary, but it can be here because the user may remove a latter project and it will no longer be required.
		$('.project input').each(remove_error);
		remove_error('project_error');
		
		var all_input = ['','',''];
		var required = [false, false, false];
		
		$('#project_experience .project').each(function(index) {
			$(this).find('input').each(function() {
				var val = $(this).val();
				if (val === undefined) val = '';
				all_input[index] += val.trim();
			});
		});
		
		//Establish which ones we'll require input for. Prevents them from only inputting #3, not one or two.
		for (var i = 2; i >= 0; i--) {
			//Input required if previous iteration marked required, or SOMETHING has input.
			required[i] = required[i] || all_input[i].length > 0;
			
			//Is this required, and we're not on the last (first) one? Then chain that the next one is required.
			if (required[i] && i > 0) {
				required[i-1] = true;
			}
		}
		
		if (required[0] == false) {
			//User didn't input anything
			valid = false; //Prevents continuation
			$('<p>You have not entered any project experience.</p>').dialog({
				title: 'No Input',
				modal: true,
				width: 500,
				buttons: {
					"Go Back": function() {
						$(this).dialog('close');
					},
					"Skip This Section": function() {
						$(this).dialog('close');

						finish_step();
						
						$('#review_project_experience').text('Skipped');
						$('#' + form_id).fadeOut('slow', function() {
							$('#submission_status').fadeIn('slow');
						});						
					}
				}
			});
		} else {
			var already_retabbed = false;
			for (var i = 1; i <= 3; i++) {
				if (required[i - 1]) {
					if (all_input[i - 1].length == 0) {
						//no input, but this one requires it.
						valid = add_error('form_errors', 'Project ' + i + ' required', 0, 'project_error');
						if (!already_retabbed) {
							already_retabbed = true;
							$('#project_' + i).prev().trigger('click');
						}
					} else {
						//This one is required, SOMETHING was input, go ahead and validate each field.
						var name_elt = $('#project_name_' + i);
						var type_elt = $('#project_type_' + i);
						var value_elt = $('#project_value_' + i);
						var completion_elt = $('#project_completion_' + i);
						var c_name_elt = $('#project_contact_name_' + i);
						var c_phone_elt = $('#project_contact_phone_' + i);
						var c_email_elt = $('#project_contact_email_' + i);

						var name_val = name_elt.val().trim();
						var type_val = type_elt.val().trim();
						var value_val = value_elt.val().trim();
						var completion_val = completion_elt.val().trim();
						var c_name_val = c_name_elt.val().trim();
						var c_phone_val = sanitize_phone(c_phone_elt.val().trim());
						var c_email_val = c_email_elt.val().trim();
						
						if (!name_val) {
							valid = add_error(name_elt, 'Name required.');
						}
						
						if (!type_val) {
							valid = add_error(type_elt, 'Type required.');
						}
						
						if (!value_val || !currency_valid(value_val)) {
							valid = add_error(value_elt, 'Valid currency required.');
						}
						
						if (!completion_val || !date_valid(completion_val, true)) { //true - allow future dates
							valid = add_error(completion_elt, 'Valid date required.');
						}
						
						if (!c_name_val) {
							valid = add_error(c_name_elt, 'Name required.');
						}
						
						if (!c_phone_val) {
							valid = add_error(c_phone_elt, 'Phone required.');
						}
						
						if (!c_email_val || !email_valid(c_email_val)) {
							valid = add_error(c_email_elt, 'Valid email required.');
						}
					}
				}
			}
		}
		
		break;
	default:
		//Shouldn't occur.
		alert("An error occurred. Please try reloading the page.");
		valid = false;
	}
	
	var valid_chars = check_valid_form_chars(form_id);
	valid = valid && valid_chars; //don't combine with above, JS short circuits expressions
	
	return valid;
}