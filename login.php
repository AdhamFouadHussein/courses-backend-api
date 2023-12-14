<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
// Database configuration
	$servername = "localhost";
	$username = "doma";
	$passwordd = "password";
	$dbname = "tafl";

try {
    // Create connection
    $conn = new mysqli($servername, $username, $passwordd, $dbname);
    // Set the charset to UTF-8
    $conn->set_charset("utf8");
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
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
        // Fetch the user data
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Correct password
            $token = bin2hex(random_bytes(16)); // This generates a crypto-secure 32 characters long token
            $stmt = $conn->prepare("UPDATE `users` SET `token`= ? WHERE `email` = ?");
            $stmt->bind_param("ss",$token, $email ); 
            // Execute the statement
            $stmt->execute();
            $stmt->close();
            $conn->close();
            $response = array('status' => 200, 'message' => 'Logged in', 'token' => $token);
            $response = array_merge($response, $user); // Merge the arrays
        } else {
            // Incorrect password
            $response = array('status' => 401, 'message' => 'Incorrect password');
        }
    } else {
        // User does not exist
        $response = array('status' => 404, 'message' => 'User does not exist');
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
?>
