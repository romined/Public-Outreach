$(document).ready(function(){
	$('.formpart').show();
	$('#submit_button').button();

	$('.phone_input, .phone_type_select, .email_input, input').change(remove_error);

	$('.name_field').watermark('Ex: John Smith');
	$('.phone_field').watermark('Ex: (555) 555-5556');
	$('.email_field').watermark('Ex: john@example.com');

	$('#submit_button').click(function() {
		var name_elt = $('#full_name_1');
		var title_elt = $('#contact_title_1');
		var req_elt = $('#request_question');
		var address_ul = $('#contact_form_address1_list');
		var phone_ul = $('#contact_form_phone_list1');
		var email_ul = $('#contact_form_email_list1');
		
		var name_val = name_elt.val().trim();
		var title_val = title_elt.val().trim();
		var req_val = req_elt.val().trim();
		
		var valid = true;
		
		if (!name_val) {
			valid = add_error(name_elt, 'Name required.');
		}
		if (!title_val) {
			valid = add_error(title_elt.next(), 'Title required.');
		}
		if (req_val.length < 4) {
			valid = add_error(req_elt, 'You must provide a request or question to submit.');
		}
		if (address_ul.children().not('.placeholder').length == 0) {
			valid = add_error('form_errors', 'Address required.', 0, 'address_error');
		}
		if (phone_ul.children().not('.placeholder').length == 0) {
			valid = add_error('form_errors', 'Phone required.', 0, 'phone_error');
		}
		if (email_ul.children().not('.placeholder').length == 0) {
			valid = add_error('form_errors', 'Email required.', 0, 'email_error');
		}
		valid = check_valid_chars(name_elt) && valid;
		valid = check_valid_chars(title_elt) && valid;
		valid = check_valid_chars(req_elt) && valid;
		
		if (valid) {
			var buttons = $(this);
			buttons.prop('disabled', true); //disable submit
			var dlg = $('<p>Uploading Information... <img src="/images/ajax.gif" class="ajax small" /></p>').dialog({
				width: 500,
				title: 'Uploading Request/Question',
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
						dlg.text('Complete! Your request/question has been captured.');
					}
					dlg.dialog({
						buttons: {
							'Ok' : function() {
								window.location.replace('/?reason=2');
							}
						}
					});
				},
				fail: function(data) {
					alert('A problem occurred while uploading your request/question. Please try again.');
					dlg.dialog('close');
					buttons.prop('disabled', false);
				}
			});
		} else {
			scroll_to_first_error();
		}
	});
});