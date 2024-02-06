<?php
    include("db_config.php");
    function updateNumberOfEmployees($departmentName)
    {
    global $pdo;
    $sql = "UPDATE department SET number_of_employees = (
                SELECT COUNT(*) FROM employee WHERE department = :department
            ) WHERE department_name = :department_name";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':department', $departmentName);
    $stmt->bindParam(':department_name', $departmentName);

    try {
        $stmt->execute();
    } catch (Exception $e) {
        // Handle the exception as needed
        echo "Error updating number of employees: " . $e->getMessage();
        }
    }

    function insertData($pdo) 
    {
        

        

        function insertDepartments($pdo)
        {
            // DEPARTMENTS
            $names_file = fopen("departments.csv", "r") or die("Unable to open file!");

            // skip header.
            fgetcsv($names_file);

            $sql = "INSERT INTO department (department_name, number_of_employees, head_office_location, manager_id) 
                VALUES (:department_name, :number_of_employees, :head_office_location, :manager_id)";

            $stmt = $pdo->prepare($sql);

            while (!feof($names_file))
            {
                $line = explode(",", fgets($names_file));

                if (empty($line[0])) continue;

                
                $department_name = $line[0];
                $number_of_employees = $line[1];
                $head_office_location = $line[2];

                $stmt->bindParam(':department_name', $department_name);
                $stmt->bindParam(':number_of_employees', $number_of_employees);
                $stmt->bindParam(':head_office_location', $head_office_location);
                $stmt->bindValue(':manager_id', null, PDO::PARAM_INT);


                try
                {
                    $stmt->execute();
                    $error = false;
                    $message = "Sample data insert successful.";		
                }
                catch(Exception $e)
                {
                    $error = true;				
                    $message = $e->getMessage();				
                }	
            }
        }

        function insertEmployees($pdo)
        {
            // EMPLOYEES
            $names_file = fopen("employees.csv", "r") or die("Unable to open file!");


            fgetcsv($names_file);

            $sql = "INSERT INTO employee (emp_id, name, address_id, salary, dob, nin, department, emergency_contact_id) 
                        VALUES (:emp_id, :name, :address_id, :salary, :dob, :nin, :department, :emergency_contact_id)";

            $sql2 = "INSERT INTO address (house_number, street_name) VALUES (:house_number, :street_name)";

            $sql3 = "INSERT INTO emergency_contact (emergency_name, emergency_relationship, emergency_phone) 
                        VALUES (:emergency_name, :emergency_relationship, :emergency_phone)";


            $stmt = $pdo->prepare($sql);
            $stmt2 = $pdo->prepare($sql2);
            $stmt3 = $pdo->prepare($sql3);

            while (!feof($names_file))
            {
                $line = explode(",", fgets($names_file));

                if (empty($line[0])) continue;

                
                $emp_id = $line[0];
                $name = $line[1];
                $address = $line[2];
                $addressParts = explode(' ', $address);
                $house_number = $addressParts[0];
                $street_name = implode(' ', array_slice($addressParts, 1));
                $salary_str = str_replace(['"','£',' ',','],'', $line[3] . $line[4]);
                $salary = floatval($salary_str);
                $dob = date('Y-m-d', strtotime(str_replace('/', '-', $line[5])));
                $nin = $line[6];
                $department = $line[7];
                $emergency_name = $line[8];
                $emergency_relationship = $line[9];
                $emergency_phone = $line[10];

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
                    $message = "Sample data insert successful.";		
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
                    $message = "Sample data insert successful.";		
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
                    $message = "Sample data insert successful.";		
                }
                catch(Exception $e)
                {
                    $error = true;				
                    $message = "second: " . $e->getMessage();				
                }
                updateNumberOfEmployees($department);
                
            }
        }
        insertDepartments($pdo);
        insertEmployees($pdo);
        
        echo "New data inserted successfully!";

            
        

        
    }
    function select_Data($pdo) 
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
?>