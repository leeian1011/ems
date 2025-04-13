<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  include('includes/dbconnection.php');

  if (!isset($_POST['event_id'], $_POST['coupon_code'], $_POST['cprice'])) {
    http_response_code(400);
    echo "Missing required parameters.";
    return;
  }

  // Assign POST values to variables
  $event_id = $_POST['event_id'];
  $coupon_code = $_POST['coupon_code'];
  $current_price = $_POST['cprice'];
  // Prepare the SQL query to insert the event organizer's data
  $sql = "SELECT * FROM coupons WHERE event_id = $event_id";
  $stmt = $dbh->prepare($sql);
  $stmt->execute();
  $results=$stmt->fetchAll(PDO::FETCH_OBJ);
  $x = $stmt->rowCount();
  if ($x == 0) {
    http_response_code(400);
    echo "invalid: rowCount return 0";
  } else {
    foreach($results as $row) { 
      if ($row->coupon_code==$coupon_code)
      {
        if ($row->discount_type=="fixed") {
          http_response_code(200);
          $new_price = $current_price - $row->discount_value;
          if ($new_price<0) {
            $new_price=(float)0;
          }
          echo "$new_price";
          return;
        }
        if ($row->discount_type=="percentage") {
          http_response_code(200);
          $pp = (float) $current_price / 100;
          $new_price = (float) $pp * (100 - (int) $row->discount_value);
          if ($new_price<0) {
            $new_price=(float)0;
          }
          echo "$new_price";
          return;
        }
        http_response_code(400);
        echo "Invalid: No discount_type";
        return;
      }
    }
    http_response_code(400);
    echo "Invalid: No matching coupon code";
    return;
  }
} else {
  http_response_code(400);
  echo "invalid: Request method";
  return;
}
?>
