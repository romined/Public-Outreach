<?php 
include($_SERVER['DOCUMENT_ROOT'] . "/include/functions.php");
insert_header("RFI Form"); 

echo get_contact_form(1, false);
echo '<form id="request_question_form" method="post">'; //wrapped in form for selector in serialization later
frame(
	get_text_element('request_question', 'Request/Question', true, 200, 'question extra_wide'),
	'Request/Question'
);
?>
</form>

<button type="button" id="submit_button">Submit</button>
<script src="/include/contact_form.js"></script>
<script src="/include/address.js"></script>
<script src="rfi.js"></script>

<?php insert_footer(); ?>