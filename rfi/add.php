<?php

include($_SERVER['DOCUMENT_ROOT'] . "/include/functions.php");

$results = array();

if (empty($_POST)) {
	header('Location: /');
}

echo process();

function process() {
	$conn = get_connection();
	if (!$conn->beginTransaction()) {
		$results['error'] = 'An error occurred communication with the database.';
		return json_encode($results);
	}
	
	$name = sanitize($_POST["full_name_1"]);
	$title_id = sanitize($_POST["contact_title_1"]);
	
	$stmt = $conn->prepare('INSERT INTO CONTACTS (CONTACT_NAME, CONTACT_TITLE_ID) VALUES (?, ?)');
	if ($stmt->execute(array($name, $title_id))) {
		$contact_id = get_identity($conn);
	} else {
		$results['error'] = 'An error occurred adding contact information.';
		$conn->rollback();
		return json_encode($results);
	}
	
	$cont = true;
	$address_counter = 0;
	while ($cont) {
		if (isset($_POST['contact_form_1_address1'][$address_counter])) {
			$contact_address_fields = array(
				'CONTACT_ID' => $contact_id,
				'ADDRESS1'   => sanitize($_POST['contact_form_1_address1'][$address_counter]),
				'ADDRESS2'   => sanitize($_POST['contact_form_1_address2'][$address_counter]),
				'ADDRESS3'   => sanitize($_POST['contact_form_1_address3'][$address_counter]),
				'CITY'       => sanitize($_POST['contact_form_1_city']    [$address_counter]),
				'ZIP'        => sanitize($_POST['contact_form_1_zip']     [$address_counter]),
				'STATE_ID'   => sanitize($_POST['contact_form_1_state']   [$address_counter]) //This one is treated special
			);
			//Removes blanks, address2&3 if needed
			$contact_address_fields = array_filter($contact_address_fields);
			
			$keys = array_keys($contact_address_fields);
			$values = array_values($contact_address_fields);
			
			$sql = 'INSERT INTO CONTACT_ADDRESSES (' . implode(',', $keys) . ') ';
			
			//Selecting only one field and filling the rest. The -2 is 1) lazy str_repeat to avoid trailing comma, and
			//2) because we're providing one parameter. This is necessary because the form captures acronym -> name,
			//and not state_id which the table requires.
			$sql .= 'SELECT ' . str_repeat('?,', count($keys)-2) . '?, STATE_ID FROM STATES WHERE STATE_ACRONYM = ?';
			
			$stmt = $conn->prepare($sql);
			if ($stmt->execute($values)) {
				$address_counter++;
			} else {
				$results['error'] = 'An error occurred adding contact addresses.';
				$conn->rollback();
				return json_encode($results);
			}
		} else { //!isset for address, no more addresses for this contact
			$cont = false;
		}
	}
	
	$cont = true;
	$phone_counter = 0;
	while ($cont) {
		if (isset($_POST['contact_form_1_phone_type'][$phone_counter])) {
			$phone_fields = array(
				'CONTACT_PHONE_NUMBER' => sanitize($_POST['contact_form_1_phone_num'] [$phone_counter], 'phone'),
				'PHONE_TYPE_ID'        => sanitize($_POST['contact_form_1_phone_type'][$phone_counter]),
				'CONTACT_ID'           => $contact_id
			);
			
			$keys = array_keys($phone_fields);
			$values = array_values($phone_fields);
			
			$sql = build_sql('CONTACT_PHONES', $keys);
			$stmt = $conn->prepare($sql);
			if ($stmt->execute($values)) {
				$phone_counter++;
			} else {
				$results['error'] = 'An error occurred adding contact phones.';
				$conn->rollback();
				return json_encode($results);
			}
		} else { //!isset for phone, no more phone numbers for this contact
			$cont = false;
		}
	}
	
	$cont = true;
	$email_counter = 0;
	while ($cont) {
		if (isset($_POST['contact_form_1_email'][$email_counter])) {
			$email_fields = array(
				'CONTACT_EMAIL_ADDRESS' => sanitize($_POST['contact_form_1_email'] [$email_counter]),
				'CONTACT_ID'            => $contact_id
			);
			
			$keys = array_keys($email_fields);
			$values = array_values($email_fields);
			
			$sql = build_sql('CONTACT_EMAIL', $keys);
			$stmt = $conn->prepare($sql);
			if ($stmt->execute($values)) {
				$email_counter++;
			} else {
				$results['error'] = 'An error occurred adding contact email addresses.';
				$conn->rollback();
				return json_encode($results);
			}
		} else { //!isset for email, no more email numbers for this contact
			$cont = false;
		}
	}
	
	if (isset($_POST['contact_organization_type_1'])) {
		$stmt = $conn->prepare('INSERT INTO CONTACT_ORGANIZATION_TYPE_DETAIL (CONTACT_ID, CONTACT_ORGANIZATION_TYPE_ID) VALUES (?, ?)');
		$stmt->bindParam(1, $contact_id);
		$stmt->bindParam(2, $org_type_id);
		foreach ($_POST['contact_organization_type_1'] as $dirty_org_type_id) {
			$org_type_id = sanitize($dirty_org_type_id);
			if (!$stmt->execute()) {
				$results['error'] = 'An error occurred adding contact organization types.';
				$conn->rollback();
				return json_encode($results);
			}
		}
	}

	$question = sanitize($_POST['request_question']);
	
	$stmt = $conn->prepare('INSERT INTO RFI_ENTRIES (REQUEST_QUESTION, CONTACT_ID) VALUES (?, ?)');
	if (!$stmt->execute(array($question, $contact_id))) {
		$results['error'] = 'An error occurred adding your question.';
		$conn->rollback();
		return json_encode($results);
	};
	
	$conn->commit();
	$results['status'] = 'success';
	return json_encode($results);
}

?>