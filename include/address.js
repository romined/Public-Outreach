//Pairs with get_address functionality
//Relies on outreach.js

$(document).ready(function() {
	//Catch keyboard input of zipcode, fill in city/state after 5th key entered.
	$(".zip_input").keyup(function(e) {
		var el = $(this);
		var parent = el.parent().parent();
		var val = el.val();
		var city_elt = parent.find(".city_input");
		var state_elt = parent.find('.state_select');
		var both_elt = city_elt.add(state_elt);
		
		//Fires AJAX request to lookup city/state based on zip. User can edit after it finishes.
		//e.which -- ASCII keycode. > 40 just eliminates control characters. Behavior here is odd with keypad, don't change to just 0-9.
		if (e.which > 40 && val.length == 5 && is_int(val)) {
			el.after('<img src="/images/ajax.gif" class="ajax small" />');
			$.ajax({
				url: "http://ziptasticapi.com/" + val,
				cache: false,
				dataType: "json",
				type: "GET",
				success: function(result, success) {
					if (!result.error) {
						city_elt.val(result.city);
						state_elt.children("option[value='" + result.state + "']").prop('selected', true);
					}
				},
				error: function(result, status, error) {
					console.log(status + ", " + error);
				},
				complete: function(result, success) {
					//make everything editable
					both_elt.prop('disabled', false);
					both_elt.trigger('change');
					state_elt.trigger("chosen:updated");
					$('.state_wrapper, .city_wrapper').removeClass('disabled', 'slow');

					//remove ajax png
					el.siblings('img.ajax.small').remove();
				}
			});
		}
	});
	
	//Add watermark, the grey text suggestions that disappear when user types in field
	$('.address1_input').watermark('Ex: 123 Main St');
	$('.address2_input').watermark('Ex: Suite 505');
	$('.address3_input').watermark('Ex: c/o Acme Inc.');
	$('.zip_input').watermark('#####');
	
	$('.city_input, .state_select').prop('disabled', 'true');
	$('.state_wrapper, .city_wrapper').addClass('disabled');
	
	$('.add_address_button').click(function() {
		var wrapper = $(this).parent();
		var parent_form_id = $(this).closest('form').prop('id');
		
		var address1 = wrapper.find('.address1_input');
		var address2 = wrapper.find('.address2_input');
		var address3 = wrapper.find('.address3_input');
		var city = wrapper.find('.city_input');
		var state = wrapper.find('.state_select');
		var zip = wrapper.find('.zip_input');
		
		var address1_val = address1.val().trim().toUpperCase();
		var address2_val = address2.val().trim().toUpperCase();
		var address3_val = address3.val().trim().toUpperCase();
		var city_val = city.val().trim().toUpperCase();
		var state_val = state.val(); //already upper
		var zip_val = zip.val().trim();
		
		var ul = wrapper.find(".input_list");

		var valid = true;

		if (!address1_val) {
			valid = add_error(address1, 'Address1 required');
		}
		if (!address2_val && address3_val) {
			valid = add_error(address2, 'Required if using address3');
		}
		
		if (!city_val) {
			valid = add_error(city, 'City required');
		}
		
		if (!state_val) {
			valid = add_error(state.next(), 'State required'); //Chosen wraps this up, need jump up
		}
		
		if (!zip_val || !is_int(zip_val) || zip_val < 1000 || zip_val > 99999) {
			valid = add_error(zip, 'Invalid zipcode');
		}
		
		if (ul.children().length > 3) {
			valid = add_error(address1, 'Only 3 addresses allowed');
		}
		
		//Check for illegal characters.
		wrapper.find('input').each(function() {
			valid = check_valid_chars(this) && valid;
		});

		if (valid) {
			var address_string = address1_val;
			if (address2_val) address_string += '<br />' + address2_val;
			if (address3_val) address_string += '<br />' + address3_val;
			address_string += "<br />" + city_val + ", " + state_val + " " + zip_val;
		
			address_string += '<input type="hidden" name="' + parent_form_id + '_address1[]" value="' + address1_val + '" />';
			address_string += '<input type="hidden" name="' + parent_form_id + '_address2[]" value="' + address2_val + '" />';
			address_string += '<input type="hidden" name="' + parent_form_id + '_address3[]" value="' + address3_val + '" />';
			address_string += '<input type="hidden" name="' + parent_form_id + '_city[]" value="' + city_val + '" />';
			address_string += '<input type="hidden" name="' + parent_form_id + '_state[]" value="' + state_val + '" />';
			address_string += '<input type="hidden" name="' + parent_form_id + '_zip[]" value="' + zip_val + '" />';
			
			ul.children('.placeholder').remove();
			ul.append('<li class="ui-widget-content">' + address_string + '</li>');
			ul.selectable("enable");
			
			address1.val('');
			address2.val('');
			address3.val('');

			remove_error('address_error');
		}
	});
});