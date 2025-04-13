<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include('includes/dbconnection.php');
    // Get the data from the POST request
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = md5($_POST['password']); // Hash the password for security

    // Prepare the SQL query to insert the event organizer's data
    $sql = "INSERT INTO admin (AdminName, UserName, MobileNumber, Email, Password, Status) 
            VALUES (?, ?, ?, ?, ?, 0)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssss", $name, $username, $phone, $email, $password);
        if ($stmt->execute()) {
            echo "<script>alert('Event Organizer created successfully.')</script>";
            echo "<script type='text/javascript'> document.location ='dashboard_admin.php'; </script>";                  
        } else {
            echo "Error: " . $stmt->error;
        }
    }
} else {
    echo "Invalid request method.";
}
?>
