<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
    // Database configuration
    $servername = "localhost";
    $username = "doma";
    $passwordd = "password";
    $dbname = "tafl";

    // Create connection
    $conn = new mysqli($servername, $username, $passwordd, $dbname);
    // Set the charset to UTF-8
    $conn->set_charset("utf8");
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get JSON as a string
    $json_str = file_get_contents('php://input');

    // Get as an object
    $json_obj = json_decode($json_str);

    $email = $json_obj->email;
    $password = $json_obj->password;

    // Prepare and bind
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, now we verify the password
        // Prepare and bind
        $stmt = $conn->prepare("SELECT
        full_name,
        email,
        username,
        phone_number,
        city,
        address
         FROM users WHERE email = ? AND password = ?");
        $stmt->bind_param("ss", $email, $password); 

        // Execute the statement
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();
        $stmt->close();
        $conn->close();
        
        if ($result->num_rows > 0) {
             // Correct password
              // Fetch all rows
       $rows = array();
       while($row = $result->fetch_assoc()) {
           $rows[] = $row;
       }
            $token = bin2hex(random_bytes(16)); // This generates a crypto-secure 32 characters long token
            $conn = new mysqli($servername, $username, $passwordd, $dbname);
            $stmt = $conn->prepare("UPDATE `users` SET `token`= ? WHERE `email` = ?");
            $stmt->bind_param("ss",$token, $email ); 
            // Execute the statement
            $stmt->execute();
            $stmt->close();
            $conn->close();
            $response = array('status' => 200, 'message' => 'Logged in', 'token' => $token);
            $response = array_merge($response, $rows[0]); // Merge the arrays
        } else {
            // Incorrect password
            $response = array('status' => 401, 'message' => 'Incorrect password');
        }
    } else {
        // User does not exist
        $response = array('status' => 404, 'message' => 'User does not exist');
    }

   

    header('Content-type: application/json');
    echo json_encode($response);
?>
