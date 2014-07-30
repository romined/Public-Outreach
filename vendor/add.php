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
	
	$fields = array(
		'ORGANIZATION_NAME' => sanitize($_POST['organization_name']),
		'COMPANY_OWNER_NAME' => sanitize($_POST['company_owner_name']),
		'DATE_BUSINESS_FOUNDED' => sanitize($_POST['date_business_founded']),
		'BUSINESS_STRUCTURE_TYPE_ID' => sanitize($_POST['type_business_structure']),
		'CERTIFICATION_AUTHORITY_ID' => sanitize($_POST['certification_authority']),
		'CERTIFICATION_NUMBER' => sanitize($_POST['certification_number']),
		'CERTIFICATION_EXPIRATION_DATE' => sanitize($_POST['certification_expiration_date']),
		'COMPANY_OTHER_NAME' => sanitize($_POST['business_other_name']),
		'PREEXISTING_BUSINESS_NAME' => sanitize($_POST['preexisting_business_name']),
		'HAS_FILED_BANKRUPTCY' => ($_POST['has_filed_bankruptcy_radio'] == 'yes') ? 1 : 0,
		'HAS_LEGAL_COMPLICATIONS' => ($_POST['has_legal_complications_radio'] == 'yes') ? 1 : 0,
		'HAS_SAFETY_PLAN' => ($_POST['has_safety_plan_radio'] == 'yes') ? 1 : 0,
		'MAXIMUM_BONDING_CAPACITY' => sanitize($_POST['maximum_bonding_capacity'], 'currency'),
		'REMAINING_UNCOMMITTED_BOND_CAPACITY' => sanitize($_POST['remaining_uncommitted_bond_capacity'], 'currency'),
		'CGL_AGGREGATE_LIMIT' => sanitize($_POST['cgl_aggregate_limit'], 'currency'),
		'WC_EL_AGGREGATE_LIMIT' => sanitize($_POST['wc_el_aggregate_limit'], 'currency'),
		'PL_AGGREGATE_LIMIT' => sanitize($_POST['pl_aggregate_limit'], 'currency'),
		'AUTO_AGGREGATE_LIMIT' => sanitize($_POST['auto_aggregate_limit'], 'currency'),
		'EXCESS_LIABILITY' => sanitize($_POST['excess_liability'], 'currency')
	);
	
	$fields = array_filter($fields, 'strlen');
	$keys = array_keys($fields);
	$values = array_values($fields);
	$sql = build_sql('ORGANIZATIONS', $keys);
	$stmt = $conn->prepare($sql);
	if ($stmt->execute($values)) {
		$org_id = get_identity($conn);
	} else {
		$results['error'] = 'An error occurred adding organization information.';
		$conn->rollback();
		return json_encode($results);
	}
	
	if (isset($_POST['license_types_tx'])) {
		$stmt = $conn->prepare('INSERT INTO ORGANIZATION_LICENSE_DETAIL (ORGANIZATION_ID, LICENSE_TYPE_NAME) SELECT ?, ORGANIZATION_LICENSE_TYPE_NAME FROM ORGANIZATION_LICENSE_TYPES WHERE ORGANIZATION_LICENSE_TYPE_ID = ?');
		$stmt->bindParam(1, $org_id);
		$stmt->bindParam(2, $license_id);
		foreach ($_POST['license_types_tx'] as $dirty_license_id) {
			$license_id = sanitize($dirty_license_id);
			if (!$stmt->execute()) {
				$results['error'] = 'An error occurred adding license information.';
				$conn->rollback();
				return json_encode($results);
			}
		}
	}
	if ($_POST['other_license_type']) {
		$other = sanitize($_POST['other_license_type']);
		$sql = build_sql('ORGANIZATION_LICENSE_DETAIL', array('ORGANIZATION_ID', 'LICENSE_TYPE_NAME'));
		$stmt = $conn->prepare($sql);
		if (!$stmt->execute(array($org_id, $other))) {
			$results['error'] = 'An error occurred adding license information.';
			$conn->rollback();
			return json_encode($results);		
		}
	}

	if (isset($_POST['service_types'])) {
		$stmt = $conn->prepare('INSERT INTO ORGANIZATION_SERVICE_DETAIL (ORGANIZATION_ID, ORGANIZATION_SERVICE_NAME) SELECT ?, ORGANIZATION_SERVICE_TYPE_NAME FROM ORGANIZATION_SERVICE_TYPES WHERE ORGANIZATION_SERVICE_TYPE_ID = ?');
		$stmt->bindParam(1, $org_id);
		$stmt->bindParam(2, $service_id);
		foreach ($_POST['service_types'] as $dirty_service_id) {
			$service_id = sanitize($dirty_service_id);
			if (!$stmt->execute()) {
				$results['error'] = 'An error occurred adding service information.';
				$conn->rollback();
				return json_encode($results);
			}
		}
	}
	if ($_POST['other_service_type']) {
		$other = sanitize($_POST['other_service_type']);
		$sql = build_sql('ORGANIZATION_SERVICE_DETAIL', array('ORGANIZATION_ID', 'ORGANIZATION_SERVICE_NAME'));
		$stmt = $conn->prepare($sql);
		if (!$stmt->execute(array($org_id, $other))) {
			$results['error'] = 'An error occurred adding service information.';
			$conn->rollback();
			return json_encode($results);		
		}
	}
	
	/********************************
	*********  CONTACTS  ************
	********************************/
	
	$add_another_1 = ($_POST['add_another_1_radio'] == 'yes');
	$add_another_2 = ($_POST['add_another_2_radio'] == 'yes');

	$contact_count = 1;
	if ($add_another_1) $contact_count++;
	if ($add_another_2) $contact_count++;
	
	for ($num = 1; $num <= $contact_count; $num++) {
		$name = sanitize($_POST["full_name_{$num}"]);
		$title_id = sanitize($_POST["contact_title_{$num}"]);
		
		$stmt = $conn->prepare('INSERT INTO CONTACTS (CONTACT_NAME, CONTACT_TITLE_ID) VALUES (?, ?)');
		if ($stmt->execute(array($name, $title_id))) {
			$contact_id = get_identity($conn);
		} else {
			$results['error'] = 'An error occurred adding contact information.';
			$conn->rollback();
			return json_encode($results);
		}
		
		$stmt = $conn->prepare('INSERT INTO ORGANIZATION_CONTACT_DETAIL (ORGANIZATION_ID, CONTACT_ID) VALUES (?, ?)');
		if (!$stmt->execute(array($org_id, $contact_id))) {
			$results['error'] = 'An error occurred adding contact information.';
			$conn->rollback();
			return json_encode($results);
		}
		
		$cont = true;
		$address_counter = 0;
		while ($cont) {
			if (isset($_POST['contact_form_' . $num . '_address1'][$address_counter])) {
				$contact_address_fields = array(
					'CONTACT_ID' => $contact_id,
					'ADDRESS1'   => sanitize($_POST['contact_form_' . $num . '_address1'][$address_counter]),
					'ADDRESS2'   => sanitize($_POST['contact_form_' . $num . '_address2'][$address_counter]),
					'ADDRESS3'   => sanitize($_POST['contact_form_' . $num . '_address3'][$address_counter]),
					'CITY'       => sanitize($_POST['contact_form_' . $num . '_city']    [$address_counter]),
					'ZIP'        => sanitize($_POST['contact_form_' . $num . '_zip']     [$address_counter]),
					'STATE_ID'   => sanitize($_POST['contact_form_' . $num . '_state']   [$address_counter]) //This one is treated special
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
			if (isset($_POST['contact_form_' . $num . '_phone_type'][$phone_counter])) {
				$phone_fields = array(
					'CONTACT_PHONE_NUMBER' => sanitize($_POST['contact_form_' . $num . '_phone_num'] [$phone_counter], 'phone'),
					'PHONE_TYPE_ID'        => sanitize($_POST['contact_form_' . $num . '_phone_type'][$phone_counter]),
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
			if (isset($_POST['contact_form_' . $num . '_email'][$email_counter])) {
				$email_fields = array(
					'CONTACT_EMAIL_ADDRESS' => sanitize($_POST['contact_form_' . $num . '_email'] [$email_counter]),
					'CONTACT_ID'           => $contact_id
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
		
		if (isset($_POST['contact_organization_type_' . $num])) {
			$stmt = $conn->prepare('INSERT INTO CONTACT_ORGANIZATION_TYPE_DETAIL (CONTACT_ID, CONTACT_ORGANIZATION_TYPE_ID) VALUES (?, ?)');
			$stmt->bindParam(1, $contact_id);
			$stmt->bindParam(2, $org_type_id);
			foreach ($_POST['contact_organization_type_' . $num] as $dirty_org_type_id) {
				$org_type_id = sanitize($dirty_org_type_id);
				if (!$stmt->execute()) {
					$results['error'] = 'An error occurred adding contact organization types.';
					$conn->rollback();
					return json_encode($results);
				}
			}
		}
	}
	
	/********************************
	********  ORG CONTACT  **********
	********************************/
	
	$cont = true;
	$address_counter = 0;
	while ($cont) {
		if (isset($_POST['organization_contact_info_address1'][$address_counter])) {
			$address_fields = array(
				'ORGANIZATION_ID' => $org_id,
				'ADDRESS1'   => sanitize($_POST['organization_contact_info_address1'][$address_counter]),
				'ADDRESS2'   => sanitize($_POST['organization_contact_info_address2'][$address_counter]),
				'ADDRESS3'   => sanitize($_POST['organization_contact_info_address3'][$address_counter]),
				'CITY'       => sanitize($_POST['organization_contact_info_city']    [$address_counter]),
				'ZIP'        => sanitize($_POST['organization_contact_info_zip']     [$address_counter]),
				'STATE_ID'   => sanitize($_POST['organization_contact_info_state']   [$address_counter]) //This one is treated special
			);
			//Removes blanks, address2&3 if needed
			$address_fields = array_filter($address_fields);
			
			$keys = array_keys($address_fields);
			$values = array_values($address_fields);
			
			$sql = 'INSERT INTO ORGANIZATION_ADDRESSES (' . implode(',', $keys) . ') ';
			
			//Selecting only one field and filling the rest. The -2 is 1) lazy str_repeat to avoid trailing comma, and
			//2) because we're providing one parameter. This is necessary because the form captures acronym -> name,
			//and not state_id which the table requires.
			$sql .= 'SELECT ' . str_repeat('?,', count($keys)-2) . '?, STATE_ID FROM STATES WHERE STATE_ACRONYM = ?';
			
			$stmt = $conn->prepare($sql);
			if ($stmt->execute($values)) {
				$address_counter++;
			} else {
				$results['error'] = 'An error occurred adding organization addresses.';
				$conn->rollback();
				return json_encode($results);
			}
		} else { //!isset for address, no more addresses for this organization
			$cont = false;
		}
	}
	
	$cont = true;
	$phone_counter = 0;
	while ($cont) {
		if (isset($_POST['organization_contact_info_phone_type'][$phone_counter])) {
			$phone_fields = array(
				'ORGANIZATION_PHONE_NUMBER' => sanitize($_POST['organization_contact_info_phone_num'] [$phone_counter], 'phone'),
				'PHONE_TYPE_ID'             => sanitize($_POST['organization_contact_info_phone_type'][$phone_counter]),
				'ORGANIZATION_ID'                => $org_id
			);
			
			$keys = array_keys($phone_fields);
			$values = array_values($phone_fields);
			
			$sql = build_sql('ORGANIZATION_PHONES', $keys);
			$stmt = $conn->prepare($sql);
			if ($stmt->execute($values)) {
				$phone_counter++;
			} else {
				$results['error'] = 'An error occurred adding organization phones.';
				$conn->rollback();
				return json_encode($results);
			}
		} else { //!isset for phone, no more phone numbers for this organization
			$cont = false;
		}
	}
	
	$cont = true;
	$email_counter = 0;
	while ($cont) {
		if (isset($_POST['organization_contact_info_email'][$email_counter])) {
			$email_fields = array(
				'ORGANIZATION_EMAIL' => sanitize($_POST['organization_contact_info_email'] [$email_counter]),
				'ORGANIZATION_ID'    => $org_id
			);
			
			$keys = array_keys($email_fields);
			$values = array_values($email_fields);
			
			$sql = build_sql('ORGANIZATION_EMAIL', $keys);
			$stmt = $conn->prepare($sql);
			if ($stmt->execute($values)) {
				$email_counter++;
			} else {
				$results['error'] = 'An error occurred adding organization email addresses.';
				$conn->rollback();
				return json_encode($results);
			}
		} else { //!isset for email, no more email numbers for this organization
			$cont = false;
		}
	}	
	
	/********************************
	*********  PROJ. EXP  ***********
	********************************/
	
	for ($num = 1; isset($_POST["project_name_{$num}"]) && $_POST["project_name_{$num}"]; $num++) {
		$project_fields = array(
			'PROJECT_NAME'          => sanitize($_POST["project_name_{$num}"]),
			'PROJECT_TYPE'          => sanitize($_POST["project_type_{$num}"]),
			'PROJECT_VALUE'         => sanitize($_POST["project_value_{$num}"], 'currency'),
			'PROJECT_COMPLETION_DATE'    => sanitize($_POST["project_completion_{$num}"]),
			'PROJECT_CONTACT_NAME'  => sanitize($_POST["project_contact_name_{$num}"]),
			'PROJECT_CONTACT_PHONE' => sanitize($_POST["project_contact_phone_{$num}"], 'phone'),
			'PROJECT_CONTACT_EMAIL' => sanitize($_POST["project_contact_email_{$num}"]),
			'ORGANIZATION_ID' => $org_id
		);
		
		$keys = array_keys($project_fields);
		$values = array_values($project_fields);
		
		$sql = build_sql('ORGANIZATION_PROJECT_EXPERIENCE', $keys);
		$stmt = $conn->prepare($sql);
		if (!$stmt->execute($values)) {
			$results['error'] = $sql . print_r($values, true);
			//$results['error'] = 'An error occurred adding project experience.';
			$conn->rollback();
			return json_encode($results);
		}
	}
	
	$conn->commit();
	$results['status'] = 'success';
	return json_encode($results);
}

?>