<?php
// Include the classes file
include 'classes.php';

// Get the API key from the classes file
const API_KEY = API_KEY;

// Define a function to get the token from Paymob
function getToken(){
  // Create a new Token object
  $token = new Token(API_KEY);
  // Return the token value
  return $token->value;
}
function getCartِAndUserInfo() {
    // Get the JSON array of numbers from the request body
    $json = file_get_contents('php://input');
    $cart = json_decode($json, true); 
    // Return the cart array
    return $cart;
  }
  
// Define a function to process the order from the course ids
function processOrder($course_ids, $url) {
  // Create a new Order object from the course ids
  $order = new Order($course_ids);
  // Send a post request to the url and return the response
  return $order->sendPostRequest($url);
}

function createPaymentKey($response, $url, $cart) : object{
  // Get user details from the cart
  $email = $cart['email'];
  $firstName = $cart['firstName'];
  $lastName = $cart['lastName'];
  $phone = $cart['phone'];
  // Create a new PaymentKey object from the response
  $paymentKey = new PaymentKey($response, $email, $firstName, $lastName, $phone);
  // Send a post request to the url and return the result
  return $paymentKey->sendPostRequest($url);
}

// Define a function to pay using the payment token and the integration id
function pay(string $payment_token, $integration_id) {
  // The pay API url depends on the payment channel you are using
  // For example, for card payments, the url is:
  $pay_url = "https://accept.paymobsolutions.com/api/acceptance/iframes/807853?payment_token=$payment_token"; // The pay API url for card payments
  // You can redirect the customer to this url or embed it in an iframe
  // For other payment channels, please check the integration guide of the desired payment method
  return $pay_url;
}

try {
    $cart = getCartِAndUserInfo();
    // Call the getToken function to get the authentication token
    $auth_token = getToken();
    // Call the processOrder function with the cart array and the order url
    $response = processOrder($cart['ids'], 'https://accept.paymobsolutions.com/api/ecommerce/orders');
    // Call the createPaymentKey function with the response and the payment key url
    $result = createPaymentKey($response, 'https://accept.paymobsolutions.com/api/acceptance/payment_keys', $cart);
    // Call the pay function with the payment token and the integration id
    $pay_url = pay($result->token, 4410231);
    echo $pay_url;
    //echo "<iframe src=\"$pay_url\" width=50% height=70%></iframe>";
    exit();
} catch (Exception $e) {
  // Echo the exception message
  echo 'Caught exception: ', $e->getMessage(), "\n";
}
?>
