<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Database configuration
$dbhost = "localhost";
$dbuser = "doma";
$dbpass = "password";
$dbname = "tafl";

// Create database connection
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
// Set the charset to UTF-8
$conn->set_charset("utf8");
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Get the path from the URL
$path = $_SERVER['PATH_INFO'];


if ($path == '/courses') {
    getCourses($conn);
} elseif (preg_match('/^\/courses\/(\d+)$/', $path, $matches)) {
    getCourse($conn, $matches[1]);
} 
elseif (preg_match('/^\/token\/(\w+)$/', $path, $matches)) {
    logByToken($conn, $matches[1]);
}
elseif (preg_match('/^\/updateUser\/(\w+)$/', $path, $matches)) {
    updateUser($conn, $matches[1]);
}
/*elseif (preg_match('/^\/lessons\/(\d+)$/', $path, $matches)){
    getLessons($conn,$matches[1]);
}*/elseif ($path == '/partners') {
    getPartners($conn);
} else {
    http_response_code(404);
    echo json_encode(array('message' => 'Not found'));
}

function logByToken($conn, $token){
    // Prepare and bind
    $stmt = $conn->prepare("SELECT token,
    full_name,
    email,
    username,
    phone_number,
    city,
    address,
    pp_src FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
       // Fetch all rows
       $rows = array();
       while($row = $result->fetch_assoc()) {
           $rows[] = $row;
       }

       $response = array('status' => 200, 'message' => 'Logged in', 'token' => $token);
       $response = array_merge($response, $rows[0]); // Merge the arrays
    } else {
       $response = array('status' => 404, 'message' => 'Please Sign in Again!');
    }

    // Echo the response as JSON
    echo json_encode($response);
}


function getCourses($conn) {
    // SQL query
    $sql = "SELECT * FROM courses";
    $result = $conn->query($sql);
    // Fetch all rows and encode into JSON
      if ($result->num_rows > 0) {
        $rows = array();
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        print json_encode($rows);
    } else {
        echo "No results found";
    }
}

function getCourse($conn, $id) {
     // SQL query
     $sql = "SELECT * FROM courses WHERE id = ?";
     $stmt = $conn->prepare($sql);
     $stmt->bind_param("i", $id);
     $stmt->execute();
     $result = $stmt->get_result();
     
     if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
            $rows[] = $row;
         }
         print json_encode($rows);
     } else {
         echo "0 results";
     }
}
function getPartners($conn) {
    // SQL query
    $sql = "SELECT * FROM partners";
    $result = $conn->query($sql);

    // Fetch all rows and encode into JSON
    if ($result->num_rows > 0) {
        $rows = array();
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        print json_encode($rows);
    } else {
        echo "No results found";
    }
}
function updateUser($conn, $token){
    $jsonData = json_decode(file_get_contents('php://input'), true);

    $stmt = $conn->prepare("UPDATE `users` SET `full_name`= ?, `phone_number`= ?, `city`= ?, `address`= ? WHERE `token` = ?");

    $stmt->bind_param("sssss", $jsonData['full_name'], $jsonData['phone_number'], $jsonData['city'], $jsonData['address'], $token);

    if ($stmt->execute()) {
        $response = array('status' => 'success', 'message' => 'User updated successfully');

    } else {
           $response = array('status' => 'error', 'message' => 'Failed to update user');
    }
    echo json_encode($response);
}

$conn->close();
?>
