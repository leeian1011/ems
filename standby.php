<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include('includes/dbconnection.php');
    // Get the data from the POST request
    $coupon_code = $_POST['coupon_code'];
    $event_id = $_POST['event_id'];
    $current_price = $_POST['cprice'];

    // Prepare the SQL query to insert the event organizer's data
    $sql = "SELECT * FROM coupons WHERE event_id = $event_id";

    if ($stmt = $conn->prepare($sql)) {
        if ($stmt->execute()) {
          $results=$stmt->fetchAll(PDO::FETCH_OBJ);
          if ($stmt->rowCount() == 0) {
            http_response_code(400);
            echo "invalid coupon";
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
                  $pp = $current_price / 100;
                  $new_price = $pp * (100 - $row->discount_value);
                  if ($new_price<0) {
                    $new_price=(float)0;
                  }
                  echo "$new_price";
                  return;
                }
                http_response_code(400);
                echo "invalid coupon";
                return;
            }
            http_response_code(400);
            echo "invalid coupon";
            return;
          }
        } else {
            http_response_code(400);
            echo "invalid coupon";
            return;
        }
    }
} else {
  http_response_code(400);
  echo "invalid coupon";
  return;
}
}
?>
