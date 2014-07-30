//Pairs with get_contact_form functionality
//Relies on outreach.js

$(document).ready(function() {
	//Setup the phone numbers to remove errors (if any) when edited.
	$('.phone_input, .phone_type_select, .email_input').change(remove_error);
		
	//jQuery buttonize the buttons.
	$('.contact_button').button();
		
	//Add phone click handler
	$('.add_phone').click(function() {
		var form_id = $(this).closest('form').prop('id');
		
		$('#' + form_id + ' .phone_input, #' + form_id + ' .phone_type_select').removeClass('element_error').siblings('.input_error').remove();

		var phone_elt = $('#' + form_id + ' .phone_input');
		var type_elt = $('#' + form_id + ' .phone_type_select');
		var ul = $('#' + form_id + " .phones .input_list");
		
		var phone_type = type_elt.children(":selected").text();
		var valid = true;
		
		//Remove all common number symbols, returns nothing if invalid
		var phone_num_raw = sanitize_phone(phone_elt.val());
		var phone_string;
		
		if (!phone_type) {
			valid = add_error(type_elt.next(), 'Please choose a phone type.');
		}

		if (ul.children().length > 5) {
			valid = add_error(phone_elt, "Maximum exceeded.");
		} else if (!phone_num_raw) {
			valid = add_error(phone_elt, 'Invalid phone number.');
		} else {
			var phone_string = phone_type + ': ' + "(" + phone_num_raw.substring(0,3) + ") " + phone_num_raw.substring(3, 6) + "-" + phone_num_raw.substring(6,10);

			ul.children().each(function() {
				if ($(this).text() == phone_string) {
					valid = add_error(phone_elt, 'Number already on list.');
					return false; //break each
				}
			});
		}
		
		if (valid) {
			//Double quotes are not allowed anywhere, so shouldn't be able to break out
			phone_string += '<input type="hidden" name="' + form_id + '_phone_type[]" value="' + type_elt.val() + '" /><input type="hidden" name="' + form_id + '_phone_num[]" value="' + phone_num_raw + '" />'; 
			
			ul.children('.placeholder').remove();
			ul.append('<li class="ui-widget-content">' + phone_string + '</li>');
			ul.selectable("enable");
			
			phone_elt.val('');
			type_elt.val('');
			type_elt.trigger("chosen:updated");
			
			remove_error('phone_error');
		}
	});

	//Add email click handler
	$('.add_email').click(function() {
		var form_id = $(this).closest('form')[0].id;
				
		var email_elt = $('#' + form_id + ' .email_input')
		var email_val = email_elt.val();
		var ul = $('#' + form_id + " .email .input_list");
		var err = '';
		
		//test input against regex
		if (ul.children().length > 3) {
			err = 'Maximum exceeded.';
		} else if (!email_valid(email_val)) {
			err = 'Invalid e-mail address.';
		} else {
			//check for duplicates
			ul.children().each(function() {
				if ($(this).text().toLowerCase() == email_val.toLowerCase()) {
					err = 'Address already captured.';
					return false; //break each
				}
			});
		}
		if (err) {
			add_error(email_elt, err);
		} else {
			//Double quotes are not allowed anywhere, so shouldn't be able to break out
			email_string = email_val + '<input type="hidden" name="' + form_id + '_email[]" value="' + email_val + '" />';
		
			ul.children('.placeholder').remove();
			ul.append('<li class="ui-widget-content">' + email_string + '</li>');
			ul.selectable("enable");
			
			email_elt.val('');
			
			remove_error('email_error');
		}
	});

	//Add Next button click handler
	$('.contact_next_button').click(function() {
		var valid = true;
		
		var frm = $(this).closest('form');
		
		var name_elt = frm.find('.name_input');
		var title_elt = frm.find('.title_select');
		var address_list = frm.find('.active_addresses ul');
		var phone_list = frm.find('.active_phones ul');
		var email_list = frm.find('.active_email ul');
		
		if (!name_elt.val()) {
			valid = add_error(name_elt, 'Name is required.');
		}
		
		if (!title_elt.val()) {
			valid = add_error(title_elt.next(), 'Title is required.');
		}
		
		if (address_list.children().not('.placeholder').length == 0) {
			valid = add_error('form_errors', 'Address required.', 0, 'address_error');
		}

		if (phone_list.children().not('.placeholder').length == 0) {
			valid = add_error('form_errors', 'Phone number required.', 0, 'phone_error');
		}

		if (email_list.children().not('.placeholder').length == 0) {
			valid = add_error('form_errors', 'Email address required.', 0, 'email_error');
		}
		
		var valid_chars = check_valid_form_chars(frm.prop('id'));
		valid = valid && valid_chars //Don't combine with above, JS short circuits expressions.
		
		if (valid) {		
			var another = frm.find('.add_another:checked').val();
			
			var frm = $(this).closest('form');
			frm.fadeOut('slow', function(){
				var fadein_elt;
				
				if (another == 'yes') {
					fadein_elt = frm.next();
					$('html,body').animate({scrollTop: 0}, "slow");
				} else {
					var contacts = 1;
					switch (frm.prop('id')) {
					case 'contact_form_3':
						contacts++;
						//fallthrough
					case 'contact_form_2':
						contacts++;
						//fallthrough
					case 'contact_form_1':
						$('#review_add_contacts').text(contacts);
						break;
					default:
						//Shouldn't ever happen.
						alert("An error occurred. Please try again.");
					}

					finish_step();
					fadein_elt = $('#submission_status')
				}
				
				fadein_elt.fadeIn('slow');
			});
		} else {
			scroll_to_first_error();
		}
	});
	
	//There is a disable parameter for initialization, but it has slightly different output
	$('.input_list').selectable();
	$('.input_list').selectable("disable");
	
	$('.input_list').on("selectableselected selectableunselected", function(event, ui) {
		//Only enable the remove button when something is selected.
		var is_disabled =$(this).has('.ui-selected').length == 0;
		$(this).siblings('button.input_list_remove_button').button(is_disabled ? "disable" : "enable");
	});
	
	$('button.input_list_remove_button').click(function() {
		var ul = $(this).siblings('ul.input_list');
		ul.children('.ui-selected').remove();
		if (ul.children().length == 0) {
			ul.append('<li class="placeholder ui-widget-content">(None)</li>');
			ul.selectable("disable");
			$(this).button("disable")
		}
	});
}); 