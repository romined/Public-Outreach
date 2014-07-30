<?php 
include($_SERVER['DOCUMENT_ROOT'] . "/include/functions.php");
insert_header("Vendor Form"); 

$steps = array(
	'add_contacts' => 'Add Contacts',
	'organization_contact_info' => 'Add Organization Contact Info',
	'company_info' => 'Add Company Information',
	'insurance_info' => 'Enter Insurance Information',
	'project_experience' => 'Enter Project Experience',
	'review' => 'Review and Submit',
);

function insert_project_experience($max) {
	echo '<p>List up to three different projects your company has worked on</p>';
	echo '<div id="project_experience_wrapper">';
	for ($num = 1; $num <= $max; $num++) {
		echo "<h3>Project {$num}</h3><div id=\"project_{$num}\" class=\"project\">";

		frame(
			get_text_element('project_name_' . $num, 'Project Name', false, 200, 'project_name') .
			get_text_element('project_type_' . $num, 'Project Type', false, 200, 'project_type') .
			get_text_element('project_value_' . $num, 'Project Value', false, 21, 'monetary_field') .
			get_text_element('project_completion_' . $num, 'Date of Completion', false, 10, 'project_completion_date date_field'),
			'Project Information'
		);
		
		frame(
			get_text_element('project_contact_name_' . $num, 'Name', false, 100, 'name_field wide') .
			get_text_element('project_contact_phone_' . $num, 'Phone', false, 14, 'phone_field wide') .
			get_text_element('project_contact_email_' . $num, 'Email', false, 100, 'email_field wide'),
			'Project Contact'
		);
		
		echo "</div>"; //project_i
	}
	echo '</div>'; //project_experience_wrapper
}

?>
<div id="submission_status" class="ui-widget">
	<h3>Steps to Complete Vendor Form</h3>
	<table id="steps_table" class="ui-corner-all">
		<tr><th>Step</th><th>Description</th><th>Status</th><th>Actions</th></tr>
		<?php
		$counter = 1;
		foreach ($steps as $id => $text)
			echo "<tr id=\"{$id}_row\" class=\"checklist_row" . ($id == 'review' ? ' disabled' : '') . "\"><td>" . $counter++ . "</td><td>{$text}</td><td><img id=\"{$id}_status\" class=\"status_checkbox incomplete\" /></td><td><button type=\"button\" id=\"{$id}_edit\" class=\"edit_button\">Edit</button></tr>";
		?>
	</table>
	<div id="progress_bar"><span id="progress_text"></span></div>
	<input type="button" id="status_next_button" value="Next" />
</div>

<?php echo get_contact_form(3); ?>

<form id="organization_contact_info" class="formpart ui-widget" action="post">
	<h3>Add Organization Contact Info</h3>
	<?php
	echo get_address('organization_contact_info','');
	echo get_phone('organization_contact_info', '');
	echo get_email('organization_contact_info', '');
	insert_formpart_next();
	?>
</form>

<form id="company_info" class="formpart ui-widget" action="post">
	<h3>Add Company Information</h3>
	<?php
	frame(
		get_text_element('organization_name', 'Organization Name', true, 100, 'wide') .
		get_text_element('company_owner_name', 'Company Owner Name', true, 100, 'name_field') .
		get_text_element('date_business_founded', 'Date Business Founded', true, 10, 'date_field') .
		get_select_element('type_business_structure', 'Type of Business Structure', 'SELECT ORGANIZATION_BUSINESS_STRUCTURE_TYPE_ID, ORGANIZATION_BUSINESS_STRUCTURE_TYPE_NAME FROM ORGANIZATION_BUSINESS_STRUCTURE_TYPES ORDER BY ORGANIZATION_BUSINESS_STRUCTURE_TYPE_ID', true, 'Select business structure...', '', '', false),
		"Organization Information"
	);

	frame(
		get_select_element('certification_authority', 'Certification Authority', 'SELECT ORGANIZATION_CERTIFICATION_AUTHORITY_ID, ORGANIZATION_CERTIFICATION_AUTHORITY_NAME FROM ORGANIZATION_CERTIFICATION_AUTHORITIES ORDER BY ORGANIZATION_CERTIFICATION_AUTHORITY_ID', false, 'Select certification authority...') .
		get_text_element('certification_number', 'Certification Number', false, 50) .
		get_text_element('certification_expiration_date', 'Certification Expiration Date', false, 10, 'date_field'),
		"Certification Information"
	);

	frame(
		get_select_element('license_types_tx', 'License Type (in Texas)', 'SELECT ORGANIZATION_LICENSE_TYPE_ID, ORGANIZATION_LICENSE_TYPE_NAME FROM ORGANIZATION_LICENSE_TYPES ORDER BY ORGANIZATION_LICENSE_TYPE_ID', false, 'Select license type(s)...', 'wide', '', true) .
		get_text_element('other_license_type', 'License Type (if other)', false, 30),
		"License Information"
	);

	frame(
		get_select_element('service_types', 'Service Types Offered', 'SELECT ORGANIZATION_SERVICE_TYPE_ID, ORGANIZATION_SERVICE_TYPE_NAME FROM ORGANIZATION_SERVICE_TYPES ORDER BY ORGANIZATION_SERVICE_TYPE_ID', false, 'Select service type(s)...', 'wide', '', true) .
		get_text_element('other_service_type', 'Service Type Offered (if other)', false, 30),
		"Services"
	);

	frame(
		get_binary_radio('business_other', 'Does this company use any other name?', false) .
		get_text_element('business_other_name', 'Other Name', true, 100, 'wide', 'hidden')
	);

	frame(
		get_binary_radio('preexisting_business', 'Is the company a continuation of a pre-existing business?', false) .
		get_text_element('preexisting_business_name', 'Pre-existing Business Name', true, 100, 'wide', 'hidden')
	);
	
	frame(get_binary_radio('has_filed_bankruptcy', 'Has owner or principal ever filed bankruptcy?', false));
	frame(get_binary_radio('has_legal_complications', 'Are there any current judgements, suits, sanctions, debarments, or claims pending against your company that could negatively impact your ability to perform?', false));
	frame(get_binary_radio('has_safety_plan', 'Does you company have a safety plan?', false));
	
	frame(
		get_text_element('maximum_bonding_capacity', "What is your company's maximum bonding capacity?", false, 21, 'monetary_field') .
		get_text_element('remaining_uncommitted_bond_capacity', "Amount of remaining uncommitted bond capacity", false, 21, 'monetary_field'),
	"Bonding Information"
	);
	
	insert_formpart_next();

	?>
</form>
<form id="insurance_info" class="formpart ui-widget" action="post">
	<?php
	echo get_text_element('cgl_aggregate_limit', 'Enter CGL Aggregate Limit Amount', false, 21, 'monetary_field');
	echo get_text_element('wc_el_aggregate_limit', 'Enter WC/EL Aggregate Limit Amount', false, 21, 'monetary_field');
	echo get_text_element('pl_aggregate_limit', 'Enter PL Aggregate Limit Amount', false, 21, 'monetary_field');
	echo get_text_element('auto_aggregate_limit', 'Enter Auto Aggregate Limit Amount', false, 21, 'monetary_field');
	echo get_binary_radio('excess_liability_choice', 'Excess Liability?', false);
	echo get_text_element('excess_liability', 'Enter Excess Liability Amount', false, 21, 'monetary_field', 'hidden');

	insert_formpart_next();
	?>
</form>

<form id="project_experience" class="formpart ui-widget" action="post">
	<?php
	insert_project_experience(3);
	insert_formpart_next();
	?>
</form>
<form id="review" class="formpart ui-widget" action="post">
	<table id="review_steps">
		<tr><td class="review_step">Contacts:<td id="review_add_contacts">0</td></tr>
		<tr><td class="review_step">Business Addresses:<td id="review_organization_contact_info">0</td></tr>
		<tr><td class="review_step">Company Information:<td id="review_company_info">Incomplete</td></tr>
		<tr><td class="review_step">Insurance Information:<td id="review_insurance_info">Incomplete</td></tr>
		<tr><td class="review_step">Projects Entered:<td id="review_project_experience">Incomplete</td></tr>
	</table>
	<?php echo get_back_button(); ?>
	<button type="button" id="submit_button">Submit Vendor</button>
</form>

<script src="/include/contact_form.js"></script>
<script src="/include/address.js"></script>
<script src="vendor.js"></script>
<?php insert_footer(); ?>