<?php 

include($_SERVER['DOCUMENT_ROOT'] . "/include/functions.php");

$dirty_reason = isset($_GET['reason']) ? $_GET['reason'] : ''; //Don't do anything but comparisons with this.

$msg = "";
if ($dirty_reason == 1) {
	$msg = "Thank you! Your vendor information has been accepted into the system.";
} elseif ($dirty_reason == 2) {
	$msg = "Thank you! Please expect a response within ten business days.";
} else {
	$bad_words = array('select', 'insert', 'update', 'delete');
	foreach ($bad_words as $bad_word) {
		if (stripos($dirty_reason, $bad_word) !== FALSE) {
			header('Location: http://amzn.com/1118380932');
			exit(0);
		}
	}
}

insert_header("Home"); 
if ($msg) {
	echo '<div id="message_wrapper"><span class="status_message ui-corner-all">' . $msg . '</span></div>';
}
?>
<p>Welcome to the Public Outreach site.</p>
<?php insert_footer(); ?>