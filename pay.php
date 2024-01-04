<?php
// Database configuration
$dbhost = "127.0.0.1";
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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $transId = $_POST["transId"];
   //echo $transId;
}
$sql = "SELECT * FROM transactions WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $transId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
       $rows[] = $row;
    }
   // print json_encode($rows);
  

} else {
    echo "0 results";
}


    $obj = new Transaction($rows[0]);
  //print $trans->getFirstName();
  function request($obj) {
    $amount = $obj->getTotal();
    $transId = $obj->getId();
    $email = $obj->getEmail();
    $address = $obj->getAddress();
    $city = $obj->getCity();
    $givenName = $obj->getFirstName();
    $surname = $obj->getLastName();
    $url = "https://eu-prod.oppwa.com/v1/checkouts";
    $data = "entityId=8ac7a4c98a5dd899018a5f272d6500ef" .
    "&amount=" . $amount .
    "&currency=SAR" .
    "&merchantTransactionId=" . $transId .
    "&customer.email=" . $email .
    "&billing.street1=" . $address .
    "&billing.city=" . $city .
    "&customer.givenName=" . $givenName .
    "&customer.surname=" . $surname .
    "&paymentType=DB";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                   'Authorization:Bearer OGFjN2E0Yzk4YTVkZDg5OTAxOGE1ZjI1ZWY4NjAwZWJ8ZjR0cmdxc2g1Zg=='));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // this should be set to true in production
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $responseData = curl_exec($ch);
    if(curl_errno($ch)) {
        return curl_error($ch);
    }
    curl_close($ch);
    return $responseData;
}

$obj = new Transaction($rows[0]);
$amount = $obj -> getTotal();
$responseData = request($obj);
//echo $responseData;
$responseObj = json_decode($responseData); // renamed $obj to $responseObj
$id = $responseObj->id;
//echo $id;
//echo "https://eu-test.oppwa.com/v1/paymentWidgets.js?checkoutId={$id}"; 
class Transaction {
    // Properties
    private $id;
    private $user_first_name;
    private $user_last_name;
    private $address;
    private $city;
    private $email;
    private $total;
    private $courses;
    private $status;

    // Constructor
    public function __construct($row) {
        $this->id = $row['id'];
        $this->user_first_name = $row['user_first_name'];
        $this->user_last_name = $row['user_last_name'];
        $this->address = $row['address'];
        $this->city = $row['city'];
        $this->email = $row['email'];
        $this->total = $row['total'];
        $this->courses = $row['courses'];
        $this->status = $row['status'];
    }

    // Getters
    public function getId() {
        return $this->id;
    }

    public function getFirstName() {
        return $this->user_first_name;
    }

    public function getLastName() {
        return $this->user_last_name;
    }

    public function getAddress() {
        return $this->address;
    }

    public function getCity() {
        return $this->city;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getTotal() {
        return $this->total;
    }

    public function getCourses() {
        return $this->courses;
    }

    public function getStatus() {
        return $this->status;
    }
}
?>


<html>
<head>
<script src="https://eu-prod.oppwa.com/v1/paymentWidgets.js?checkoutId=<?php echo $id; ?>">

</script>
<script> history.replaceState({}, "", location.href.split("?")[0]); </script>
</head>
<body>
<form action="https://alkhabir.co/#/my-courses" class="paymentWidgets" data-brands="AMEX MADA MASTER VISA"></form>
    <p style="justify-content: center; text-align: center;">Total Amount:
     <?php 
        echo $amount;
     ?> SAR</p>
<script>  var wpwlOptions = {
            style: "card",
            locale: "ar"
        }
        </script>
</body>
</html>