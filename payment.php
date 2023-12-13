<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Include the classes file
require_once 'classes.php';

// Define a function to get the token from Paymob
function getToken(){
  $token = new Token(API_KEY);
  return $token->value;
}

function getCartAndUserInfo() {
    $json = file_get_contents('php://input');
    $cart = json_decode($json, true); 
    return $cart;
}

function processOrder($course_ids, $url) {
  $order = new Order($course_ids);
  return $order->sendPostRequest($url);
}

function createPaymentKey($response, $url, $cart) {
  $email = $cart['email'];
  $firstName = $cart['firstName'];
  $lastName = $cart['lastName'];
  $phone = $cart['phone'];
  $paymentKey = new PaymentKey($response, $email, $firstName, $lastName, $phone);
  return $paymentKey->sendPostRequest($url);
}

function pay($payment_token, $integration_id) {
  $pay_url = "https://accept.paymobsolutions.com/api/acceptance/iframes/807853?payment_token=$payment_token";
  return $pay_url;
}

try {
    $cart = getCartAndUserInfo();
    $auth_token = getToken();
    $response = processOrder($cart['ids'], 'https://accept.paymobsolutions.com/api/ecommerce/orders');
    $result = createPaymentKey($response, 'https://accept.paymobsolutions.com/api/acceptance/payment_keys', $cart);
    $pay_url = pay($result->token, 4410231);
    echo json_encode(array("url" => $pay_url));
    exit();
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(array('error' => $e->getMessage()));
}
?>
