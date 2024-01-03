<?php
class MyClass {
    public $amount;
    public function __construct() {
      $this->amount = $_GET['total'];
    }
  }

function request() {
  //  $amount = $_GET['total'];
    $obj = new MyClass();
    //echo $obj->amount;
    $amount = $obj->amount;
    //$string = "&amount=" . $amount;
	$url = "https://eu-test.oppwa.com/v1/checkouts";
    $data = "entityId=8ac7a4c98a5dd899018a5f272d6500ef" .
    "&amount=" . $amount .
    "&currency=SAR" .
    "&paymentType=DB";
//$data = str_replace("amount=60", "amount=".$amount, $data);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                   'Authorization:Bearer OGFjN2E0Yzk4YTVkZDg5OTAxOGE1ZjI1ZWY4NjAwZWJ8ZjR0cmdxc2g1Zg=='));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$responseData = curl_exec($ch);
	if(curl_errno($ch)) {
		return curl_error($ch);
	}
	curl_close($ch);
	return $responseData;
}
$responseData = request();
//echo $responseData;
$obj = json_decode($responseData);
$id = $obj->id;
//echo $id;
//echo "https://eu-test.oppwa.com/v1/paymentWidgets.js?checkoutId={$id}"; 
?>

<html>
<head>
<script src="https://eu-test.oppwa.com/v1/paymentWidgets.js?checkoutId=<?php echo $id; ?>">

</script>
<script> history.replaceState({}, "", location.href.split("?")[0]); </script>
</head>
<body>
<form action="https://hmjswbs17o.sharedwithexpose.com/#/my-courses/" class="paymentWidgets" data-brands="AMEX MADA MASTER VISA"></form>
    <p style="align-items: center;">Total Amount:
         <?php 
            $obj = new MyClass();
            echo $obj->amount;
        ?> SAR</p>
<script>  var wpwlOptions = {
            style: "card",
            locale: "ar"
        }
        </script>
<?php
?>
</body>
</html>
