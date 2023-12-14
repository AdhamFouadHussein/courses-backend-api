<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$servername="localhost";
$username="doma";
$passwordd="password";
$dbname="tafl";

try {
    $conn=new mysqli($servername,$username,$passwordd,$dbname);
    $conn->set_charset("utf8");
    if($conn->connect_error){
        throw new Exception("Connection failed: ".$conn->connect_error);
    }

    $json_str=file_get_contents('php://input');
    $json_obj=json_decode($json_str, true);

    $email=mysqli_real_escape_string($conn, $json_obj['email']);
    $password=$json_obj['password'];

    $stmt=$conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $result=$stmt->get_result();

    if($result->num_rows>0){
        $user=$result->fetch_assoc();
        if(password_verify($password, $user['password'])){
            $token=bin2hex(random_bytes(16));
            $stmt=$conn->prepare("UPDATE `users` SET `token`= ? WHERE `email` = ?");
            $stmt->bind_param("ss",$token,$email);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            $response=array('status'=>200,'message'=>'Logged in','token'=>$token);
            $response=array_merge($response,$user);
        }else{
            $response=array('status'=>401,'message'=>'Incorrect password');
        }
    }else{
        $response=array('status'=>404,'message'=>'User does not exist');
    }
    echo json_encode($response);
}catch(Exception $e){
    http_response_code(500);
    echo json_encode(array('error'=>$e->getMessage()));
}
?>
