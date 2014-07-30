<?php

#Contains primary functions for site functionality including header and footer.

#List out menu pages and their URLs
function get_pages() {
	return array(
		'Home' => '/',
		'Vendor Form' => '/vendor',
		'RFI Form'	=> '/rfi',
	);
}

#Print header which should be used on all pages leaving the div MAIN for contents
function insert_header($current_page) {
	$pages = get_pages();
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html>
	<head>
		<title>Public Outreach Site - <?php echo $current_page ?></title>
		<link rel="stylesheet" type="text/css" href="/css/outreach.css" />
		<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css" />
		<link rel="stylesheet" type="text/css" href="/css/jquery.multiselect.css" />
		<link rel="stylesheet" type="text/css" href="/include/chosen/chosen.min.css" />
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
		<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
		<script src="/include/jquery.watermark.min.js"></script>
		<script src="/include/outreach.js"></script>
		<script src="/include/jquery.multiselect.min.js"></script>
		<script src="/include/chosen/chosen.jquery.min.js"></script>
	</head>
	<body>
	<div id="layout">
		<div id="wrapper">
			<div id="header">
				<img id="logo" src="/images/trwd.png" />
			</div>
			<div id="menu">
				<ul>
					<?php
					foreach ($pages as $page => $url) {
						echo '<li';
						if ($current_page == $page) {
							echo ' class="active"';
						}
						echo '><a href="' . $url . '">' . $page . '</a></li>';
					}
					?>
				</ul>
			</div>
			<div id="main">
				<h1><?php echo $current_page ?></h1>
				<div id="form_errors"></div>
	<?php
}

#Complements insert_header, closes out structure and adds footer code
function insert_footer() {
	?>
			</div>
		</div>
	</div>
	</body>
	</html>
	<?php
}

#Output HTML for a full contact form, up to {max} contacts
#User needs to include contact_form.js
function get_contact_form($max, $include_buttons = true) {
	if (func_num_args() < 1 || func_num_args() > 2) {
		die("Contact form: Invalid number of arguments");
	}
	
	$results = "";
	
	for ($num = 1; $num <= $max; $num++) {
		$results .= "<form id=\"contact_form_{$num}\" name=\"contact_form_{$num}\" class=\"contact_form formpart ui-widget\" method=\"post\">";
		$results .= "<div id=\"contact_info_{$num}\" class=\"contact_info section_frame ui-corner-all\">";
		$results .= "<h3>Contact " . ($num > 1 ? "{$num} " : '') . "Information</h3>";

		$results .= get_text_element('full_name_' . $num, 'Full Name', true, 80, 'name_input name_field wide');
		$results .= get_select_element('contact_title_' . $num, 'Title', "SELECT CONTACT_TITLE_ID, CONTACT_TITLE_NAME FROM CONTACT_TITLES ORDER BY CONTACT_TITLE_ID", true, "Choose title...", 'title_select');
		$results .= get_select_element('contact_organization_type_' . $num, 'Organization Type', 'SELECT CONTACT_ORGANIZATION_TYPE_ID, CONTACT_ORGANIZATION_TYPE_NAME FROM CONTACT_ORGANIZATION_TYPES ORDER BY CONTACT_ORGANIZATION_TYPE_ID', false, 'Select license type(s)...', 'wide', '', true); 
		 
		$results .= '</div>'; //contact_info_n

		$results .= get_address('contact_form', $num);
		$results .= get_phone('contact_form', $num);
		$results .= get_email('contact_form', $num);

		if ($num == $max) {
			if ($num > 1) { //If only 1, no need to display any sort of max message
				$results .= '<div class="element_wrapper">';
				$results .= '<h3>Add another contact?</h3>';
				$results .= "<div class=\"note\">Only {$max} contacts allowed</div>";
				$results .= "<input type=\"hidden\" name=\"add_another_{$num}\" class=\"add_radio\" value=\"no\" />";
				$results .= '</div>'; //element_wrapper
			}
		} else {
			$results .= get_binary_radio("add_another_{$num}", "Add another contact?", false, 'add_another');
		}
		if ($include_buttons) {
			$results .= get_back_button() . "<button type=\"button\" id=\"contact_next_{$num}\" class=\"contact_next_button\">Next</button>";
		}
		$results .= '</form>';
	}
	
	return $results;
}

//Output HTML for a list
function get_list($id, $class, $prompt) {
	$results = '';
	
	$results .= "<div id=\"active_{$id}\" class=\"{$class} section_frame\">";
	$results .= "<h3>{$prompt}:</h3>";
	$results .= "<ul id=\"{$id}\" class=\"input_list\">";
	$results .= '<li class="placeholder ui-widget-content">(None)</li>';
	$results .= '</ul>';
	$results .= '<button type="button" class="input_list_remove_button contact_button" disabled="disabled">Remove</button>'; 
	$results .= '</div>';
	
	return $results;
}

//Output HTML for inputting addresses
//User page needs to include address.js
function get_address($form_id, $suffix) {
	$results = '';
	if (func_num_args() != 2) {
		die("Invalid arguments on insert_address.");
	}
	
	$results .= "<div id=\"{$form_id}_addresses{$suffix}\" class=\"addresses section_frame ui-corner-all\"><h3>Add Addresses</h3>";

	for ($j = 1; $j <= 3; $j++) {
		$results .= get_text_element("{$form_id}_address{$suffix}_address_line{$j}", "Address{$j}", $j == 1, 100, "address{$j}_input wide");
	}
	$results .= get_text_element("{$form_id}_address{$suffix}_zip", 'Zip', true, 5, 'zip_input', '', "[0-9]*");
	$results .= get_text_element("{$form_id}_address{$suffix}_city", 'City', true, 50, "city_input", 'city_wrapper');
	$results .= get_select_element("{$form_id}_address{$suffix}_state", 'State', "SELECT STATE_ACRONYM, UPPER(STATE_NAME) STATE_NAME FROM STATES ORDER BY STATE_ID", true, 'Select state...', "state_select", "state_wrapper");
	
	$results .= "<button type=\"button\" id=\"{$form_id}_address{$suffix}_add\" class=\"add_address_button\">Add Address</button>";
	
	$results .= get_list("{$form_id}_address{$suffix}_list", "active_addresses", "Included Addresses");
	
	$results .= '</div>'; //addresses wrapper
	
	return $results;
}

function get_phone($form_id, $suffix) {
	$results = '';

	$results .= "<div id=\"{$form_id}_phones{$suffix}\" class=\"phones section_frame ui-corner-all\">";
	$results .= '<div class="add_phone_wrapper">';
	$results .= '<h3>Add Phone Numbers</h3>';
	
	$results .= get_text_element("{$form_id}_phone{$suffix}", 'Phone Number', false, 14, 'phone_input phone_field');
	$results .= get_select_element("{$form_id}_phone_type{$suffix}", 'Phone Type', "SELECT PHONE_TYPE_ID, PHONE_TYPE_NAME FROM PHONE_TYPES ORDER BY PHONE_TYPE_ID", false, "Choose phone type...", 'phone_type_select');
	
	$results .= '<div class="element_wrapper">';
	$results .= "<button type=\"button\" id=\"{$form_id}_add_phone{$suffix}\" class=\"add_phone contact_button\">Add Phone</button>";
	$results .= '</div>'; //element_wrapper
	$results .= '</div>'; //add_phone_wrapper
	
	$results .= get_list("{$form_id}_phone_list{$suffix}", "active_phones", "Current Phones");

	$results .= "</div>"; //phones_n
	
	return $results;
}

function get_email($form_id, $suffix) {
	$results = '';
	
	$results .= "<div id=\"{$form_id}_email{$suffix}\" class=\"email section_frame ui-corner-all\">";
	$results .= '<div class="add_email_wrapper">';
	$results .= '<h3>Add Email Addresses</h3>';
	
	$results .= get_text_element("{$form_id}_email{$suffix}", 'Email Address', false, 80, 'email_input email_field wide');
	$results .= '<div class="element_wrapper">';
	$results .= "<button type=\"button\" id=\"{$form_id}_add_email{$suffix}\" class=\"add_email contact_button\">Add Email</button>";
	$results .= "</div>"; //element_wrapper
	$results .= "</div>"; //add_email_wrapper
	
	$results .= get_list("{$form_id}_email_list{$suffix}", "active_email", "Current Email Addresses");

	$results .= '</div>'; //email_n

	return $results;
}

//Get a text input element
function get_text_element($id, $name, $required, $maxlength, $class="", $wrapper_class="", $pattern="") {
	$results = '';
	
	if (func_num_args() < 4 || func_num_args() > 7) {
		die("Invalid text_element arguments.");
	}

	$results .= '<div class="element_wrapper' . ($wrapper_class ? ' ' . $wrapper_class : '') . '">';
	$results .= '<label for="' .  $id . '">' . $name . ($required ? required() : '') . ':</label>';
	$results .= '<input type="text" id="' . $id . '" name="' . $id . '" maxlength="' . $maxlength . '"' . ($class ? ' class="' . $class . '"' : '') . ($pattern ? " pattern=\"{$pattern}\"" : '') . ' />';
	$results .= '</div>';	
	
	return $results;
}

//Get a single or multi select element
function get_select_element($id, $name, $query, $required, $placeholder="- SELECT -", $class="", $wrapper_class="", $multiple=false) {
	$results = '';
	
	if (func_num_args() < 4 || func_num_args() > 8) {
		die("Invalid text_element arguments.");
	}
	
	$results .= '<div class="element_wrapper' . ($wrapper_class ? ' ' . $wrapper_class : '') . '">';
	$results .= '<label for="' . $id . '">' . $name . ($required ? required() : '') . ':</label>';
	$results .= '<select id="' . $id . '" name="' . $id . ($multiple ? '[]' : '') . '"' . ($class ? ' class="' . $class . '"' : '') . ($multiple ? ' multiple="multiple"' : ' data-placeholder="' . $placeholder . '"') .  '>';
	if (!$multiple) {
		$results .= '<option value=""></option>';
	}
	$connection = get_connection();
	$stmt = $connection->prepare($query);
	$stmt->execute();
	$rows = $stmt->fetchAll();
	
	foreach ($rows as $row)
		$results .= '<option value="' . $row[0] . '">' . $row[1] . '</option>';
	$results .= '</select>';
	$results .= '</div>';
	
	return $results;
}

//Get a Yes/No radio element
function get_binary_radio($id, $prompt, $default_yes, $class='') {
	if (func_num_args() < 3 || func_num_args() > 4) {
		die('Invalid binary radio arguments.');
	}
	
	if (!$class) $class = $id;
	
	$results = '';
	
	$results .= '<div class="element_wrapper">';
	$results .= "<h3>{$prompt}</h3>";
	$results .= '<label for="' . $id . '_radio_yes">Yes</label>';
	$results .= '<input type="radio" id="' . $id . '_radio_yes" name="' . $id . '_radio" class="' . $class . '" value="yes"' . ($default_yes ? ' checked="checked"' : '') . ' /><br />';
	$results .= '<label for="' . $id . '_radio_no">No</label>';
	$results .= '<input type="radio" id="' . $id . '_radio_no" name="' . $id . '_radio" class="' . $class . '" value="no"' . ($default_yes ? '' : ' checked="checked"') . ' />';
	$results .= '</div>';
	
	return $results;
}

//Button for next, intended for use in forms
function insert_formpart_next($include_back = true) {
	if ($include_back) {
		echo get_back_button();
	}
	echo '<button type="button" class="formpart_next_button">Next</button>';
}

function get_back_button() {
	return '<button type="button" class="back_button">Back</button>';
}

//Wraps a string in a bordered (framed) div
function frame($what, $title="", $class = "") {
	echo '<div class="section_frame ui-corner-all' . ($class ? ' ' . $class : '') . '">' . ($title ? "<h3>{$title}</h3>" : '') . $what . '</div>';
}

//Text for a required * span
function required() {
	return '<span class="required">*</span>';
}

//Take an input, and remove everything that isn't a avlid char
//Type: currency, phone
function sanitize($val, $type='') {
	if ($type == 'currency') {
		//Strip out everything except numbers, dots.
		return preg_replace('/[^0-9\.]/', '', $val);
	} elseif ($type == 'phone') {
		return floatval(preg_replace('/[^0-9]/', '', $val));
	} else {
		//Strip everything has wasn't a valid character on client-side
		return preg_replace('/[^a-z0-9\.,@?!\/\$\- \']/i', '', $val);
	}
}

//Get a connection to the MSSQL DB
function get_connection() {
	try
	{
	  $connection = new PDO('odbc:Driver=FreeTDS; Server=192.168.104.242; Port=1433; Database=outreach; UID=outreach; PWD=jjLcd%N1;');
	}
	catch(PDOException $exception)
	{
	  die("Unable to open database.<br />Error message:<br /><br />$exception.");
	}
	
	return $connection;
}

//Requires at least 2 fields since it uses lazy str_repeat
function build_sql($table_name, $fields) {
	return 'INSERT INTO ' . $table_name . ' (' . implode(',', $fields) . ') VALUES (' . str_repeat('?,', count($fields)-1) . '?)';
}

function get_identity($conn) {
	$stmt = $conn->prepare('SELECT @@IDENTITY'); //MSSQL last insert for this session
	$stmt->execute();
	$result = $stmt->fetchAll();
	return $result[0][0]; //1st row, 1st column
}
?>