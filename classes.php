<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
const API_KEY = "ZXlKaGJHY2lPaUpJVXpVeE1pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SmpiR0Z6Y3lJNklrMWxjbU5vWVc1MElpd2ljSEp2Wm1sc1pWOXdheUk2T1RRMU5qQTBMQ0p1WVcxbElqb2lhVzVwZEdsaGJDSjkub2V1YkFjYWZ6ZE9YdXdNUTh3bFV1STBvV0xlT1FSRm9vcXp0UDFOb3M1bFlZY3NyX3lvdWFYZEpFZnpVVlRRdFR0WHpjbzZQNGxqTjdfT19rcW1nMFE=";
class Token {
  public $value;
  public function __construct($api_key) {

    $ch = curl_init('https://accept.paymob.com/api/auth/tokens');

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('api_key' => $api_key)));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    // Execute the cURL session and store the result
    $result = curl_exec($ch);

    curl_close($ch);
    // Decode the result and assign the token value to the value property
    $this->value = json_decode($result)->token;
  }
}

// Define the Order class
class Order {
  private $data;
  public function __construct($course_ids) {
    $items = array();
    foreach ($course_ids as $course_id) {
      // Get the course details from the database
      $course = get_course_by_id($course_id);
      // Add the course details to the items array
      $items[] = array(
        'name' => $course['title'],
        'amount_cents' => $course['newPrice'] * 100,
        'quantity' => 1,
        'description' => $course['descr']
      );
    }
    $amount_cents = 0;
    foreach ($items as $item) {
      $amount_cents += $item['amount_cents'] * $item['quantity'];
    }
    // Generate a unique merchant order id
    $merchant_order_id = uniqid();
    $this->data = array(
      'auth_token' => getToken(),
      'delivery_needed' => 'false',
      'merchant_order_id' => $merchant_order_id,
      'amount_cents' => $amount_cents,
      'currency' => 'EGP',
      'items' => $items
    );
  }

  public function sendPostRequest($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
  }
}
class PaymentKey {
  private $data;

  public function __construct($response, $email, $firstName, $lastName, $phone) {
      $response = json_decode($response, true);

      // Assign the payment key data to the data property
      $this->data = array(
          'auth_token' => getToken(),
          'amount_cents' => $response['amount_cents'],
          'expiration' => 3600,
          'order_id' => $response['id'],
          'billing_data' => array(
              'apartment' => 'NA',
              'email' => $email, // Use the email parameter here
              'floor' => 'NA',
              'first_name' => $firstName, // Use the firstName parameter here
              'street' => 'NA',
              'building' => 'NA',
              'phone_number' => $phone,
              'shipping_method' => 'NA',
              'postal_code' => 'NA',
              'city' => 'NA',
              'country' => 'NA',
              'last_name' => $lastName, // Use the lastName parameter here
              'state' => 'NA'
          ),
          'currency' => 'EGP',
          'integration_id' => 4410231,
          'lock_order_when_paid' => 'false'
      );
  }

  public function sendPostRequest($url) : object {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->data));
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
      $result = curl_exec($ch);
      curl_close($ch);
      return json_decode($result);
  }
}


// Define a helper function to get the course details from the database by id
function get_course_by_id($id) {
  // Connect to the database
  $conn = new mysqli('localhost', 'doma', 'password', 'tafl');
    // Set the charset to UTF-8
    $conn->set_charset("utf8");
  // Check the connection
  if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
  }
  // Prepare the SQL query
  $sql = "SELECT title, newPrice, descr FROM courses WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $id);
  // Execute the query and store the result
  $stmt->execute();
  $result = $stmt->get_result();
  // Fetch the course details as an associative array
  $course = $result->fetch_assoc();
  // Close the statement and the connection
  $stmt->close();
  $conn->close();
  // Return the course details
  return $course;
}
?>
