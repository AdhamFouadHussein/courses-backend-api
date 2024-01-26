<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");

$servername = "127.0.0.1";
$username = "doma";
$password = "password";
$dbname = "tafl";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    throw new Exception("Connection failed: " . $conn->connect_error);
}

$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str, true);

$username = mysqli_real_escape_string($conn, $json_obj['username']);
$email = mysqli_real_escape_string($conn, $json_obj['email']);
$passwordd = password_hash($json_obj['password'], PASSWORD_DEFAULT);

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $response = array('status' => 401, 'message' => 'Username or Email already exists');
} else {
    $token = bin2hex(random_bytes(16));
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, username, phone_number, city, address, country, state, postalCode, token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $json_obj['fullName'], $email, $passwordd, $username, $json_obj['phone_number'], $json_obj['city'], $json_obj['address'], $json_obj['country'], $json_obj['state'], $json_obj['postalCode'], $token);
    if ($stmt->execute()) {
        $response = array(
            'status' => 200, 
            'message' => 'New record created successfully',
            'username' => $username,
            'full_name' => $json_obj['fullName'],
            'email' => $email,
            'phone_number' => $json_obj['phone_number'],
            'city' => $json_obj['city'],
            'address' => $json_obj['address'],
            'country' => $json_obj['country'],
            'state' => $json_obj['state'],
            'postalCode' => $json_obj['postalCode'],
            'token' => $token
        );
    } else {
        $response = array('status' => 401, 'message' => 'Error: ' . $stmt->error);
    }
}

$conn->close();

header('Content-type: application/json');
echo json_encode($response);
?>
