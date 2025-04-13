<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include('includes/dbconnection.php');
    // Get the data from the POST request
    $admin_id = $_SESSION['odmsaid'];
    $name = $_POST['name'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $desc = $_POST['desc'];
    $price = $_POST['price']; // Hash the password for security

    // Prepare the SQL query to insert the event organizer's data
    $sql = "INSERT INTO events (event_name, event_date, event_time, description, price_standard, admin_id) 
            VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssss", $name, $date, $time, $desc, $price, $admin_id);
        if ($stmt->execute()) {
            echo "<script>alert('Event created successfully.')</script>";
            echo "<script type='text/javascript'> document.location ='dashboard_event_admin.php'; </script>";                  
        } else {
            echo "Error: " . $stmt->error;
        }
    }
} else {
    echo "Invalid request method.";
}
?>
