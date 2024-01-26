<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$dbhost = "127.0.0.1";
$dbuser = "doma";
$dbpass = "password";
$dbname = "tafl";
error_reporting(E_ERROR | E_PARSE);
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
elseif ($path == '/pay'){
    newTrans($conn);
}
elseif($path == '/fpay'){
    updateTrans($conn);
}
elseif($path == '/my-courses'){
    getPaidCourses($conn);
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
function newTrans($conn){
    $jsonData = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO transactions (user_first_name, user_last_name, address, city, email, total, courses, status, country, state, postalCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $jsonData['user_first_name'], $jsonData['user_last_name'], $jsonData['address'], $jsonData['city'], $jsonData['email'], $jsonData['total'], $jsonData['courses'], $jsonData['status'], $jsonData['country'], $jsonData['state'], $jsonData['postalCode']);
     // we set status to false as it is still a new transaction.
     if ($stmt->execute()) {
        $response = array(
            'status' => 200, 
            'message' => 'New record created successfully',
            'transId' =>$conn->insert_id,
        );
    } else {
        $response = array('status' => 401, 'message' => 'Error: ' . $stmt->error);
    }
    
    // Convert the response to JSON format
    echo json_encode($response);
    
}
function updateTrans($conn) {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $conn->prepare("UPDATE `transactions` SET `status` = 1 WHERE `id` = ?");
    $stmt->bind_param("s", $jsonData['id']);
    $response = array();

    if ($stmt->execute()) {
        $stmt = $conn->prepare("SELECT `courses`, `email` FROM `transactions` WHERE `id` = ?");
        $stmt->bind_param("s", $jsonData['id']);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $jsonData['ids'] = json_decode($row['courses'], true); // Add the courses to jsonData
                $jsonData['email'] = $row['email']; // Add the email to jsonData

                updateUserCourses($conn, $jsonData); // Call updateUserCourses with the updated jsonData
                
            }
        }
    }
}

function updateUserCourses($conn, $jsonData) {
    // Fetch the current courses of the user
    $stmt = $conn->prepare("SELECT `own_courses` FROM `users` WHERE `email` = ?");
    $stmt->bind_param("s", $jsonData['email']);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $current_courses = json_decode($row['own_courses'], true);

    // If the user has courses, append the new ones
    if ($current_courses) {
        $new_courses = array_merge($current_courses, $jsonData['ids']);
    } else {
        $new_courses = $jsonData['ids'];
    }

    // Update the user's courses
    $stmt = $conn->prepare("UPDATE `users` SET `own_courses` = ? WHERE `email` = ?");
    $stmt->bind_param("ss", json_encode($new_courses), $jsonData['email']);
    
    if ($stmt->execute()) {
        $response = array('status' => 'success', 'message' => 'User updated successfully');
    } else {
        $response = array('status' => 'error', 'message' => 'Failed to update user');
    }

    echo json_encode($response);
}
function getPaidCourses($conn) {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        // Handle preflight OPTIONS request
        header("Access-Control-Allow-Origin: http://localhost:4200");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Access-Control-Max-Age: 3600");
        header("Content-Length: 0");
        header("Content-Type: text/plain");
        header("HTTP/1.1 204 No Content");
        exit();
    }

    header("Access-Control-Allow-Origin: http://localhost:4200");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");

    // Get the email from the JSON POST request
    $jsonData = json_decode(file_get_contents('php://input'), true);

    if ($jsonData && isset($jsonData['email'])) {
        // Prepare and execute the query to get the courses from the users table
        $stmt = $conn->prepare("SELECT `own_courses` FROM `users` WHERE `email` = ?");
        $stmt->bind_param("s", $jsonData['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && isset($row['own_courses'])) {
            $course_ids = json_decode($row['own_courses'], true);

            // Prepare the query to get the courses from the courses table
            $query = "SELECT * FROM `courses` WHERE `id` IN (" . implode(',', array_fill(0, count($course_ids), '?')) . ")";
            $stmt = $conn->prepare($query);
            $types = str_repeat('s', count($course_ids));
            $stmt->bind_param($types, ...$course_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            $courses = $result->fetch_all(MYSQLI_ASSOC);
            
            // Return the courses as JSON
            header('Content-Type: application/json');
            echo json_encode($courses);
        } else {
            // Return an appropriate error response as JSON
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404);
            echo json_encode(array('error' => 'User courses not found'));
        }
    } else {
        // Handle the case where email is not provided
        header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
        echo json_encode(array('error' => 'Email not provided'));
    }
}

$conn->close(); 
?>
