$(document).ready(function(){
	$('input, select').change(remove_error);

	var li = $('#menu ul li');
	li.first().addClass('ui-corner-left');
	li.last().addClass('ui-corner-right');
	
	li.mouseover(function() {
		if ($(this).hasClass("active")) {
			var bg = "#FFA500";
		} else {
			var bg = "#607B8B";
		}
		$(this).stop().animate({backgroundColor:bg}, 300).children().stop().animate({color:'#E6E8FA'}, 300);
	}).mouseout(function() {
		if ($(this).hasClass("active")) {
			var bg = '#FFFFFF';
		} else {
			var bg = '#E6E8FA';
		}
		$(this).stop().animate({backgroundColor:bg}, 300).children().stop().animate({color:'#0000ff'}, 300);
	});
	

	$('select[multiple]').multiselect({
		selectedText: "# of # selected",
		selectedList: 2
	});

	//Use chosen UI for all non-multi select elements
	$('select').not('select[multiple]').chosen({width: "200px", disable_search_threshold: 10, allow_single_deselect: true});
	
	$('.status_message')
	.click(function() {
		$(this).hide('slow', function() {($this).remove()});
	})
	.mouseover(function() {
		$(this).stop().addClass('removable', 300);
	})
	.mouseout(function() {
		$(this).stop().removeClass('removable', 300);
	});
});

function is_int(val) {
	return (parseFloat(val) == parseInt(val) && !isNaN(val));
}

//takes jquery elt
function add_error(elt, msg, jumps_up, error_class) {
	//If passed string, assume it's an ID.
	if (typeof elt === 'string') {
		elt = $('#' + elt);
	} else {
		elt = $(elt);
	}
	
	//Makes jumps_up optional
	jumps_up = typeof jumps_up !== 'undefined' ? jumps_up : 0;
	var insert_after_elt = elt;
	for (var i = 0; i < jumps_up; i++)
		insert_after_elt = insert_after_elt.parent();

	//Ensure that there isn't already an error message in that position.
	if (!elt.hasClass('element_error')) {
		var elt_type = (elt.prop('id') == 'form_errors') ? 'div' : 'span';
		var error_elt = $('<' + elt_type + ' class="input_error ui-corner-all' + (error_class ? ' ' + error_class : '') + (elt_type == 'span' ? ' inline_error' : '') + '">' + msg + '</' + elt_type + '>').hide();
		if (elt_type == 'div') {
			if (elt.children('.' + error_class).length == 0) {
				elt.append(error_elt);
				error_elt.fadeIn('slow');
			}
		} else {
			error_elt.insertAfter(insert_after_elt).fadeIn('slow');
			elt.addClass('element_error', 'fast');
		}
	}
	
	//return false just to shortcut assignment statement for caller
	return false;
}

//Remove errors from the current elt, and all its parents & their siblings. Covers the jumps_up parameter of add_error.
//Can only accept one argument for error_class right now.
function remove_error(error_class) {
	//jquery will pass event arguments sometimes, make sure its a string as intended for use.
	if (typeof error_class !== 'string' || !error_class)
		error_class = '';
		
	var t = $(this);
	
	var tn = t.next();
	if (tn.hasClass('chosen-container')) {
		//Special case, this is a chosen-style select, need to mop up the chosen-container.
		t = t.add(tn);
	}
	
	t = t.add(t.parents()).not('#form_errors');
	t.nextAll('span.input_error' + (error_class ? '.' + error_class : '')).hide('slow', function() { $(this).remove();});
	t.removeClass('element_error ' + error_class);
	if (error_class)
		$('#form_errors .' + error_class).hide('slow', function() { $(this).remove();});
}

function remove_all_errors() {
	$('#form_errors').children().remove();
	$('.input_error').remove();
	$('.element_error').removeClass('element_error');
}

//If this is called with no errors on screen, it will do nothing.
function scroll_to_first_error() {
	//Wait for all animations to finish (animating in error elements)
	$(':animated').promise().done(function() {
		//Scroll the html/body to the top of the first error element.
		var error_msgs = $('.input_error');
		if (error_msgs.length > 0) {
			var first_err = error_msgs.eq(0);
			$.when($('html, body')
			.animate({
				//Scroll it up to slightly higher than the first error element.
				scrollTop: first_err.offset().top - 50
			}, 1000)).done(function() {	
				var toggleIt = function(count) {
					if (count > 0) {
						$.when(error_msgs.toggleClass('highlight_error', 'slow')).done(function() {
							toggleIt(count - 1);
						});
					}
				};
				
				toggleIt(4); //# toggles, should be even.
			})
			.then(function() {
				//Open accordion tab, if it has one.
				first_err.closest('.accordion_header').trigger('click');
			});
		}
	});
}

//second param optional
function date_valid(d, dates_valid_past_current_year) {
	if (typeof dates_valid_past_current_year === 'undefined') {
		dates_valid_past_current_year = false;
	}

	var valid_end_year = (dates_valid_past_current_year ? 3000 : (new Date().getFullYear()));
	
	//A little sloppy but, add in zeros where they should be to match mm/dd/yyyy format.
	if (is_int(d.charAt(2)))
		d = "0" + d;
	if (is_int(d.charAt(5)))
		d = d.substr(0,3) + '0' + d.substr(3,6);
		
	//remove any extraneous characters
	d = d.replace(/[\/ -]/g, "")
	
	var valid = true;
	//now (should be) mmddyyyy
	
	if (!is_int(d) || d.length !== 8) {
		valid = false;
	} else {
		var month = d.substr(0,2);
		var day = d.substr(2,2);
		var year = d.substr(4,4);
		
		//days in month array
		var monthLength = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];
		
		//leap year
		if(year % 400 == 0 || (year % 100 != 0 && year % 4 == 0)) {
			monthLength[1]++;
		}
		
		//check for valid component ranges
		if (
			year < 1800 || 
			parseInt(year) > valid_end_year || 
			month < 1 || 
			month > 12 ||
			day < 1 ||
			day > monthLength[month - 1]
		) {
			valid = false;
		}
	}
	
	return valid;
}

function currency_valid(c) {
	if (c === undefined) c = '';
	c = c.toString();
	c = c.replace(/[, $]/g, "");
	
	//weird return here because zero is funny in JS since its dynamic.
	return c === '' || (is_int(c * 100) && c >= 0);
}

//Takes form ID, runs check_valid_chars on every input
function check_valid_form_chars(form_id) {
	var valid = true;

	$('#' + form_id + " input").each(function() {
		valid = valid && check_valid_chars(this)
	});	

	return valid;
}

//Ensure no illegal characters are entered in input, to help prevent injection and weirdness
//Do not ever allow double quotes or angled brackets, it will break several things
function check_valid_chars(elt) {
	var valid_input = /^[a-z0-9\.,@?!\/\$\- ']*$/i;
	var user_input = $(elt).val();
	var valid = valid_input.test(user_input);

	if (!valid) {
		add_error($(elt), "Only .,-/@?!$' symbols allowed");
	}
	
	return valid;
}

//Outputs either the plain 10 digits, or nothing to indicate an invalid phone number.
function sanitize_phone(phone_num) {
	phone_num = phone_num.replace(/[\(\) -]/g, "");
	
	//North American area codes < 200
	if (phone_num.length != 10 || !is_int(phone_num) || phone_num.charAt(0) == '0' || phone_num.charAt(0) == '1')
		phone_num = '';
	
	return phone_num;
}

function email_valid(email) {
	//RFC 2822 compliant email regex
	var re = /[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/i;
		
	return re.exec(email);
}