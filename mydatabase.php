<?php



	include("db_config.php");
	include("insert_data.php");




	$sql = "CREATE DATABASE IF NOT EXISTS kilburnazon";

			try
			{
				$pdo->exec($sql);
				$error = false;
			}
			catch(Exception $e)
			{
				$error = true;
				$message = $e->getMessage();
			}

    $sqlFile = "create_tables.sql";

	try {
        $sqlContent = file_get_contents($sqlFile);

        // Check if file content is not empty
        if (!empty($sqlContent)) {
            $result = $pdo->exec($sqlContent);
            $error = false;
        } else {
            $error = true;
            $message = "SQL file is empty or not readable.";
        }
    } catch (Exception $e) {
        $error = true;
        $message = "ilki: " . $e->getMessage();
    }
	$currentMonth = date('m');

	$sql = "DROP PROCEDURE IF EXISTS GetEmployeesWithBirthday";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	
	$sql = "
			CREATE PROCEDURE GetEmployeesWithBirthday()
			BEGIN

				SELECT
					employee.emp_id,
					employee.name,
					employee.dob
				FROM
					employee
				WHERE
					MONTH(employee.dob) = $currentMonth
				ORDER BY
					DAY(employee.dob);
			END
			";


	try {
		$result = $pdo->exec($sql);
	} catch (PDOException $e) {
		echo "Error creating stored procedure: " . $e->getMessage();
	}


	function displayTable($result, $pdo)
	{
		$html = "<table style='border-collapse: collapse;'>
			<tr>
				<th style='border: 1px solid;'>emp_id</th>
				<th style='border: 1px solid;'>name</th>
				<th style='border: 1px solid;'>address</th>
				<th style='border: 1px solid;'>salary</th>
				<th style='border: 1px solid;'>dob</th>
				<th style='border: 1px solid;'>nin</th>
				<th style='border: 1px solid;'>department</th>
				<th style='border: 1px solid;'>emergency_name</th>
				<th style='border: 1px solid;'>emergency_relationship</th>
				<th style='border: 1px solid;'>emergency_phone</th>
			</tr>";

		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
		$emergencyContactId = $row['emergency_contact_id'];
		$emergencyQuery = "SELECT * FROM emergency_contact WHERE emergency_contact_id = :emergency_contact_id";
		$emergencyStmt = $pdo->prepare($emergencyQuery);
		$emergencyStmt->bindParam(':emergency_contact_id', $emergencyContactId);
		$emergencyStmt->execute();
		$emergencyRow = $emergencyStmt->fetch(PDO::FETCH_ASSOC);

		$addressId = $row['address_id'];
		$addressQuery = "SELECT house_number, street_name FROM address WHERE address_id = :address_id";
		$addressStmt = $pdo->prepare($addressQuery);
		$addressStmt->bindParam(':address_id', $addressId);
		$addressStmt->execute();
		$addressRow = $addressStmt->fetch(PDO::FETCH_ASSOC);
		$fullAddress = $addressRow['house_number'] . ' ' . $addressRow['street_name'];
			
		$html .= "
		<tr>
			<td style='border: 1px solid;'>{$row['emp_id']}</td>
			<td style='border: 1px solid;'>{$row['name']}</td>

			<td style='border: 1px solid;'>{$fullAddress}</td>


			<td style='border: 1px solid;'>{$row['salary']}</td>
			<td style='border: 1px solid;'>{$row['dob']}</td>
			<td style='border: 1px solid;'>{$row['nin']}</td>
			<td style='border: 1px solid;'>{$row['department']}</td>

			

			<td style='border: 1px solid;'>{$emergencyRow['emergency_name']}</td>
			<td style='border: 1px solid;'>{$emergencyRow['emergency_relationship']}</td>
			<td style='border: 1px solid;'>{$emergencyRow['emergency_phone']}</td>
			<td>
				<button type='button' onclick=\"selectUser(" . htmlspecialchars(json_encode($row)) . ", '{$fullAddress}', " . htmlspecialchars(json_encode($emergencyRow)) . ")\">Select</button>
			</td>

			<td>
				<button type='button' onclick=\"deleteUser('{$row['emp_id']}')\">Delete</button>
			</td>

		</tr>";
		}

		$html .= "</table>";

		return $html;
	}
	
	


	if (!empty($_POST))
	{
		
		$database_action = $_POST['database_action'];

		
		if ($database_action == "insert_table_user")
		{
			insertData($pdo);

			
		}
		
	

		if ($_POST['delete_user']) 
		{

			$emp_id = $_POST['deleted_emp_id'];
			$user_emp_id = $_POST['user_emp_id'];

			$sql = "SET @user_emp_id = :user_emp_id";
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(':user_emp_id', $user_emp_id);
			$stmt->execute();


			$getIdQuery = "SELECT address_id, emergency_contact_id, department FROM employee WHERE emp_id = :emp_id";
			$getIdStmt = $pdo->prepare($getIdQuery);
			$getIdStmt->bindParam(':emp_id', $emp_id);
			$getIdStmt->execute();
			$idRow = $getIdStmt->fetch(PDO::FETCH_ASSOC);

			$address_id = $idRow['address_id'];
			$emergency_contact_id = $idRow['emergency_contact_id'];
			$department = $idRow['department'];


			$sql = "DELETE FROM employee WHERE emp_id=:emp_id";
			$sql2 = "DELETE FROM address WHERE address_id=:address_id";
			$sql3 = "DELETE FROM emergency_contact WHERE emergency_contact_id=:emergency_contact_id";

			$stmt = $pdo->prepare($sql);
			$stmt2 = $pdo->prepare($sql2);
			$stmt3 = $pdo->prepare($sql3);

			$stmt->bindParam(':emp_id', $emp_id);
			$stmt2->bindParam(':address_id', $address_id);
			$stmt3->bindParam(':emergency_contact_id', $emergency_contact_id);




			try
			{
                $stmt->execute();
				$stmt2->execute();
				$stmt3->execute();

				$error = false;
			}
			catch(Exception $e)
			{
				$error = true;			
    			$message = $e->getMessage();				
			}	

			updateNumberOfEmployees($department);

			$sql = "SELECT * FROM employee";

			try
			{
				$result = $pdo->query($sql);
				$error = false;
				$message = displayTable($result, $pdo);
			}
			catch(Exception $e)
			{
				$error = true;
				$message = "Could not select data from table.";				
				$message .= "<br>" . $e->getMessage();				
			}
		}


		
		$emp_id_pattern = '/^\d{2}-\d{7}$/';
		$phone_pattern = '/^[0-9\s\-()]+$/';
        if ($_POST['add_user']) 
		{
            
			$sql = "INSERT INTO employee (emp_id, name, address_id, salary, dob, nin, department, emergency_contact_id) 
			VALUES (:emp_id, :name, :address_id, :salary, :dob, :nin, :department, :emergency_contact_id)";

			$sql2 = "INSERT INTO address (house_number, street_name) VALUES (:house_number, :street_name)";

			$sql3 = "INSERT INTO emergency_contact (emergency_name, emergency_relationship, emergency_phone) 
						VALUES (:emergency_name, :emergency_relationship, :emergency_phone)";

			$stmt = $pdo->prepare($sql);
			$stmt2 = $pdo->prepare($sql2);
			$stmt3 = $pdo->prepare($sql3);


            $emp_id = $_POST['emp_id'];
			
			if (!preg_match($emp_id_pattern, $emp_id)) {
				$error = true;
				echo "Invalid format. Please use dd-ddddddd.";
			}
            $name = $_POST['name'];
            $address = $_POST['address'];
			$addressParts = explode(' ', $address);
            $house_number = $addressParts[0];
            $street_name = implode(' ', array_slice($addressParts, 1));

            $salary = floatval(str_replace(['"', '£', ',', ' '], '', $_POST['salary']));
            $dob = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['dob'])));
            $nin = $_POST['nin'];
            $department = $_POST['department'];
            $emergency_name = $_POST['emergency_name'];
            $emergency_relationship = $_POST['emergency_relationship'];
            $emergency_phone = $_POST['emergency_phone'];

			if (!preg_match($phone_pattern, $emergency_phone)) {
				$error = true;
				echo "Invalid phone number format.";
			}

            $stmt->bindParam(':emp_id', $emp_id);
			
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':salary', $salary);

            $stmt->bindParam(':dob', $dob);
            $stmt->bindParam(':nin', $nin);
			$stmt->bindParam(':department', $department);

            $stmt3->bindParam(':emergency_name', $emergency_name);
            $stmt3->bindParam(':emergency_relationship', $emergency_relationship);
            $stmt3->bindParam(':emergency_phone', $emergency_phone);

			$stmt2->bindParam(':house_number', $house_number);
            $stmt2->bindParam(':street_name', $street_name);


			try
            {
                
                $stmt2->execute();

                $error = false;
            }
            catch(Exception $e)
            {
                $error = true;				
                $message = "third: " . $e->getMessage();				
            }
            $address_id = $pdo->lastInsertId();
            $stmt->bindParam(':address_id', $address_id);

            
            try
            {
                
                $stmt3->execute();

                $error = false;
            }
            catch(Exception $e)
            {
                $error = true;				
                $message = "first: " . $e->getMessage();				
            }
            
            $emergency_contact_id = $pdo->lastInsertId();
            $stmt->bindParam(':emergency_contact_id', $emergency_contact_id);

            
            try
            {
                
                $stmt->execute();

                $error = false;
            }
            catch(Exception $e)
            {
                $error = true;				
                $message = "second: " . $e->getMessage();				
            }

            updateNumberOfEmployees($department);

			$sql = "SELECT * FROM employee";

			try
			{
				$result = $pdo->query($sql);
				$error = false;
				$message = displayTable($result, $pdo);
			}
			catch(Exception $e)
			{
				$error = true;
				$message = "Could not select data from table.";				
				$message .= "<br>" . $e->getMessage();				
			}
		}



		if ($_POST['update_user']) 
		{
			$emp_id = $_POST['emp_id'];

			if (!preg_match($pattern, $emp_id)) {
				$error = true;
				$message = "Invalid format. Please use dd-ddddddd.";
			}
		
            $name = $_POST['name'];
            $address = $_POST['address'];
			$addressParts = explode(' ', $address);
            $house_number = $addressParts[0];
            $street_name = implode(' ', array_slice($addressParts, 1));

            $salary = floatval(str_replace(['"', '£', ',', ' '], '', $_POST['salary']));
            $dob = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['dob'])));
            $nin = $_POST['nin'];
            $department = $_POST['department'];
            $emergency_name = $_POST['emergency_name'];
            $emergency_relationship = $_POST['emergency_relationship'];
            $emergency_phone = $_POST['emergency_phone'];

			if (!preg_match('/^[0-9\s\-()]+$/', $emergency_phone)) {
				$error = true;
				$message = "Invalid phone number format.";
			}

			$existingIdsQuery = "SELECT address_id, emergency_contact_id FROM employee WHERE emp_id = :emp_id";
			$existingIdsStmt = $pdo->prepare($existingIdsQuery);
			$existingIdsStmt->bindParam(':emp_id', $emp_id);

			$existingIdsStmt->execute();
			$existingIdsRow = $existingIdsStmt->fetch(PDO::FETCH_ASSOC);
			$existing_address_id = $existingIdsRow['address_id'];
			$existing_emergency_contact_id = $existingIdsRow['emergency_contact_id'];


			$sql = "UPDATE employee 
            SET name=:name,  
                salary=:salary, 
                dob=:dob, 
                nin=:nin, 
                department=:department 
            WHERE emp_id=:emp_id";

			$sql2 = "UPDATE address 
			SET house_number=:house_number,  
				street_name=:street_name
			WHERE address_id=:address_id";

			$sql3 = "UPDATE emergency_contact 
			SET emergency_name=:emergency_name,  
				emergency_relationship=:emergency_relationship,
				emergency_phone=:emergency_phone
			WHERE emergency_contact_id=:emergency_contact_id";


			$stmt = $pdo->prepare($sql);
			$stmt2 = $pdo->prepare($sql2);
			$stmt3 = $pdo->prepare($sql3);

			$stmt->bindParam(':emp_id', $emp_id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':salary', $salary);

            $stmt->bindParam(':dob', $dob);
            $stmt->bindParam(':nin', $nin);
			$stmt->bindParam(':department', $department);

            $stmt3->bindParam(':emergency_name', $emergency_name);
            $stmt3->bindParam(':emergency_relationship', $emergency_relationship);
            $stmt3->bindParam(':emergency_phone', $emergency_phone);
			
			$stmt2->bindParam(':house_number', $house_number);
            $stmt2->bindParam(':street_name', $street_name);

			$stmt2->bindParam(':address_id', $existing_address_id);
        	$stmt3->bindParam(':emergency_contact_id', $existing_emergency_contact_id);



			try
            {
                
                $stmt2->execute();

                $error = false;
            }
            catch(Exception $e)
            {
                $error = true;				
                $message = "third: " . $e->getMessage();				
            }
            
            
            try
            {
                
                $stmt3->execute();

                $error = false;
            }
            catch(Exception $e)
            {
                $error = true;				
                $message = "first: " . $e->getMessage();				
            }
            
        
            
            try
            {
                
                $stmt->execute();

                $error = false;
            }
            catch(Exception $e)
            {
                $error = true;				
                $message = "second: " . $e->getMessage();				
            }

			updateNumberOfEmployees($department);

			$sql = "SELECT * FROM employee";

			try
			{
				$result = $pdo->query($sql);
				$error = false;
				$message = displayTable($result, $pdo);
			}
			catch(Exception $e)
			{
				$error = true;
				$message = "Could not select data from table.";				
				$message .= "<br>" . $e->getMessage();				
			}


		}

		if ($database_action == "select_table_user")
		{
			$sql = "SELECT * FROM employee";

			try
			{
				$result = $pdo->query($sql);
				$error = false;
				$message = displayTable($result, $pdo);
			}
			catch(Exception $e)
			{
				$error = true;
				$message = "Could not select data from table.";				
				$message .= "<br>" . $e->getMessage();				
			}
		}

		if ($database_action == "special_display")
		{
			$sql =	"SELECT
						employee.name,
						employee.department,
						emergency_contact.emergency_relationship
					FROM
						employee
					JOIN
						emergency_contact ON employee.emergency_contact_id = emergency_contact.emergency_contact_id
					WHERE
						employee.department = 'Driver'
						AND emergency_contact.emergency_relationship = 'Father';						
						";
			
			try
			{
				$result = $pdo->query($sql);
				$error = false;

			}
			catch(Exception $e)
			{
				$error = true;
				$message = "Could not select data from table.";				
				$message .= "<br>" . $e->getMessage();				
			}

			$html = "<table style='border-collapse: collapse;'>
				<tr>
					<th style='border: 1px solid;'>name</th>
					<th style='border: 1px solid;'>department</th>
					<th style='border: 1px solid;'>emergency_relationship</th>

				</tr>";

			while ($row = $result->fetch(PDO::FETCH_ASSOC))
			{
				
			$html .= "
			<tr>
				<td style='border: 1px solid;'>{$row['name']}</td>
				<td style='border: 1px solid;'>{$row['department']}</td>
				<td style='border: 1px solid;'>{$row['emergency_relationship']}</td>

			</tr>";
			}

			$html .= "</table>";

			$message = $html;
		}

		if ($database_action == "birthday")
		{
			$sql = "CALL GetEmployeesWithBirthday()";

			try
			{
				$b_result = $pdo->query($sql);
				$error = false;

			}
			catch(Exception $e)
			{
				$error = true;
				$message = "Could not select data from table.";				
				$message .= "<br>" . $e->getMessage();				
			}

			$html = "<table style='border-collapse: collapse;'>
				<tr>
					<th style='border: 1px solid;'>emp_id</th>
					<th style='border: 1px solid;'>name</th>
					<th style='border: 1px solid;'>date of birth</th>


				</tr>";

			while ($row = $b_result->fetch(PDO::FETCH_ASSOC))
			{
				
			$html .= "
			<tr>
				<td style='border: 1px solid;'>{$row['emp_id']}</td>
				<td style='border: 1px solid;'>{$row['name']}</td>
				<td style='border: 1px solid;'>{$row['dob']}</td>

			</tr>";
			}

			$html .= "</table>";

			$message = $html;

		}


		

	}

?>
<style>
	body
	{
		background-color: gray;
		color: white;
		padding: 3em;
	}

	div
	{
		border:thin solid;
		text-align: center;
		padding: 2em;
	}

	input[type=button]
	{
		background-color: green;
		color: white;
		padding: 1em 2em;
		text-decoration: none;

		cursor: pointer;
		border-radius: 2em 1em;
		width: 12em;
	}

	input[type=button]:hover
	{
		background-color: lightgreen;
	}

	a
	{
		color: white;
	}

	.error
	{
		color: red;
	}

	.success
	{
		color: green;
	}

	hr
	{
		margin:2em;
	}

	.label
	{
		float:left;
	}

</style>
<h1><a href="">KILBURNAZON</a></h1>
<div>
<form name="frm_add_user" method="POST" style="display: none;">
        <span class="label">Enter User Details:</span>
        <input type="text" name="emp_id" placeholder="Employee ID">
        <input type="text" name="name" placeholder="Name">
        <input type="text" name="address" placeholder="Address">
        <input type="text" name="salary" placeholder="Salary">
        <input type="text" name="dob" placeholder="Date of Birth">
        <input type="text" name="nin" placeholder="National Insurance No.">
        <input type="text" name="department" placeholder="Department">
        <input type="text" name="emergency_name" placeholder="Emergency_Name">
        <input type="text" name="emergency_relationship" placeholder="Emergency Relationship">
        <input type="text" name="emergency_phone" placeholder="Emergency Phone">
        <input type="submit" name="add_user" value="Insert User">
    </form>

	<form name="frm_update_user" method="POST" style="display: none;">
        <span class="label">Change User Details:</span>
        <input type="text" name="emp_id" placeholder="Employee ID">
        <input type="text" name="name" placeholder="Name">
        <input type="text" name="address" placeholder="Address">
        <input type="text" name="salary" placeholder="Salary">
        <input type="text" name="dob" placeholder="Date of Birth">
        <input type="text" name="nin" placeholder="National Insurance No.">
        <input type="text" name="department" placeholder="Department">
        <input type="text" name="emergency_name" placeholder="Emergency_Name">
        <input type="text" name="emergency_relationship" placeholder="Emergency Relationship">
        <input type="text" name="emergency_phone" placeholder="Emergency Phone">
        <input type="submit" name="update_user" value="Update User">
    </form>

	<form name="frm_delete_user" method="POST" style="display: none;">
        <span class="label">Please enter your employee ID:</span>
        <input type="text" name="user_emp_id" placeholder="Employee ID">
		<input type="hidden" name="deleted_emp_id" id="deleted_emp_id">
        <input type="submit" name="delete_user" value="Delete User">
    </form>






	<form name="frm_database_action" method="POST">
		

		<span class="label">EMPLOYEE</span>
		<input type="button" name="add_user" value="ADD" onclick="toggleAddUserForm();">
		<input type="button" name="insert_table_user" value="INSERT" onclick="submit_action('insert_table_user');">
		<input type="button" name="select_table_user" value="DISPLAY" onclick="submit_action('select_table_user');">
		<input type="button" name="special_display" value="SPECIAL DISPLAY" onclick="submit_action('special_display');">
		<input type="button" name="birthday" value="BIRTHDAYS" onclick="submit_action('birthday');">





		<?php
			if (isset($error))
			{
				$style = $error ? "error" : "success";
				echo ("<p class='$style'>$message</p>");			
			}
		?>

		<input type="hidden" name="database_action" id="database_action">
	</form>

    

</div>

<script>

    function toggleAddUserForm() 
    {
        var addUserForm = document.querySelector('form[name="frm_add_user"]');
        addUserForm.style.display = addUserForm.style.display === 'none' ? 'block' : 'none';
    }

	function toggleUpdateUserForm() 
    {
        var updateUserForm = document.querySelector('form[name="frm_update_user"]');
        updateUserForm.style.display = updateUserForm.style.display === 'none' ? 'block' : 'none';
    }

	function toggleDeleteUserForm() 
    {
        var deleteUserForm = document.querySelector('form[name="frm_delete_user"]');
        deleteUserForm.style.display = deleteUserForm.style.display === 'none' ? 'block' : 'none';
    }

	function submit_action(action)
	{
		document.getElementById("database_action").value = action;
		frm_database_action.submit();
	}

	function selectUser(userData, fullAddress, emergencyRow)	
	{
		toggleUpdateUserForm();
		var updateUserForm = document.forms['frm_update_user'];
		updateUserForm.elements['emp_id'].value = userData.emp_id;
		updateUserForm.elements['name'].value = userData.name;
		updateUserForm.elements['address'].value = fullAddress;
		updateUserForm.elements['salary'].value = userData.salary;
		updateUserForm.elements['dob'].value = userData.dob;
		updateUserForm.elements['nin'].value = userData.nin;
		updateUserForm.elements['department'].value = userData.department;
		updateUserForm.elements['emergency_name'].value = emergencyRow.emergency_name;
		updateUserForm.elements['emergency_relationship'].value = emergencyRow.emergency_relationship;
		updateUserForm.elements['emergency_phone'].value = emergencyRow.emergency_phone;
		
	}

	function deleteUser(deleted_empId)	
	{
		toggleDeleteUserForm();
		var deleteUserForm = document.forms['frm_delete_user'];
        deleteUserForm.elements['deleted_emp_id'].value = deleted_empId;
	}

</script>