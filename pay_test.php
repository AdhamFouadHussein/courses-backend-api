<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

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

class Transaction {
    private $id;
    private $userFirstName;
    private $userLastName;
    private $address;
    private $city;
    private $email;
    private $total;
    private $country;
    private $state;
    private $postalCode;
    public function __construct($id, $firstName, $lastName, $address, $city, $email, $total, $country, $state, $postalCode) {
        $this->id = $id;
        $this->userFirstName = $firstName; // Change variable name here
        $this->userLastName = $lastName;   // Change variable name here
        $this->address = $address;
        $this->city = $city;
        $this->email = $email;
        $this->total = $total;
        $this->country = $country;
        $this->state = $state;
        $this->postalCode = $postalCode;
    }
    

    public function getId() {
        return $this->id;
    }

    public function getUserFirstName() {
        return $this->userFirstName;
    }

    public function getUserLastName() {
        return $this->userLastName;
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
    public function getCountry() {
        return $this->country;
    }

    public function getState() {
        return $this->state;
    }

    public function getPostalCode() {
        return $this->postalCode;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $transId = $_POST["transId"];
    
    // Prepare and execute SQL query with prepared statement
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->bind_param("i", $transId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $transaction = new Transaction(
            $row['id'],
            $row['user_first_name'],
            $row['user_last_name'],
            $row['address'],
            $row['city'],
            $row['email'],
            $row['total'],
            $row['country'], 
            $row['state'],
            $row['postalCode']
        );
        $responseData = requestPayment($transaction);
        $responseObj = json_decode($responseData);
        $id = $responseObj->id;
        $paymentJsUrl = "https://eu-test.oppwa.com/v1/paymentWidgets.js?checkoutId=".$id;
    } else {
        echo "0 results";
        exit;
    }
} else {
    echo "Invalid request method";
    exit;
}


function requestPayment($transaction) {
    $apiUrl = "https://eu-test.oppwa.com/v1/checkouts";
    $entityId = "8ac7a4c98a5dd899018a5f272d6500ef"; // Default entity ID
    $bearer = "OGFjN2E0Yzk4YTVkZDg5OTAxOGE1ZjI1ZWY4NjAwZWJ8ZjR0cmdxc2g1Zg=="; // Default bearer token
    
    // Modify entity ID and bearer token based on the path
    $pathTokens = explode("/", $_SERVER['REQUEST_URI']);
    $path = end($pathTokens);
    $paymentType = "DB";
    if ($path == 'mpay') {
        $entityId = "8ac7a4c98a5dd899018a5f2f851f0102";
        $bearer = "OGFjN2E0Yzk4YTVkZDg5OTAxOGE1ZjI1ZWY4NjAwZWJ8ZjR0cmdxc2g1Zg==";
        $paymentType = "PA";
    }
    
    $amount = $transaction->getTotal();
    $transId = $transaction->getId();
    $email = $transaction->getEmail();
    $address = $transaction->getAddress();
    $city = $transaction->getCity();
    $givenName = $transaction->getUserFirstName();
    $surname = $transaction->getUserLastName();
    $country = $transaction -> getCountry();
    $state = $transaction -> getState();
    $postalCode = $transaction -> getPostalCode();
    $currency = "SAR";
   
    $transactionInfo = [
        'currencyCode' => 'SAR', // Set the currency code here
        // Other transaction info properties...
    ];
    $data = http_build_query([
        "entityId" => $entityId,
        "amount" => $amount,
        "currency" => $currency,
        "merchantTransactionId" => $transId,
        "customer.email" => $email,
        "billing.street1" => $address,
        "billing.city" => $city,
        "customer.givenName" => $givenName,
        "customer.surname" => $surname,
        "paymentType" => $paymentType,
        "testMode" => "INTERNAL",
        "customParameters[3DS2_enrolled]" => "true",
        "billing.country" => $country,
        "billing.state" => $state,
        "billing.city" => $city,
        //"currencyCode" => "SAR",
        //'transactionInfo' => json_encode($transactionInfo),
    ]);

    // Make POST request to the payment API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $bearer,
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // this should be set to true in production
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

$responseData = requestPayment($transaction);
$responseObj = json_decode($responseData);
$id = $responseObj->id;
$paymentJsUrl = "https://eu-test.oppwa.com/v1/paymentWidgets.js?checkoutId=".$id;
?>

<html>
<head>
    <script src="<?php echo $paymentJsUrl; ?>"></script>
    <script>history.replaceState({}, "", location.href.split("?")[0]);</script>
</head>
<body>
    <form action="http://localhost:4200/#/my-courses" class="paymentWidgets" data-brands="AMEX MADA MASTER VISA"></form>
    <p style="text-align: center;">Total Amount: <?php echo $transaction->getTotal(); ?> SAR</p>
    <script>
        var wpwlOptions = {
            style: "card",
            locale: "ar"
        };
    </script>
</body>
</html>