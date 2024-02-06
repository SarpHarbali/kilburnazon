<?php

	$host = "localhost";
	$dbname = "kilburnazon";
	$username = "root";
	$password = "23111";

	try
	{
		$pdo = new PDO("mysql:host=$host;", $username, $password);


		try
		{
			$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

		}
		catch (PDOException $e)
		{

		}

	}
	catch (PDOException $e)
	{

	}
?>