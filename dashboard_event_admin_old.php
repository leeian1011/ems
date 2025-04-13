<?php 
include('includes/checklogin.php');
check_login("dashboard_event_admin");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="shortcut icon" href="assets/images/favicon.jpg" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 60%;
            margin: 50px auto;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 8px;
        }
        h1 {
            text-align: center;
        }
        .btn {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .form-group {
            margin: 10px 0;
        }
        input[type="text"], input[type="email"], input[type="number"], input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Event Organizer Dashboard</h1>

    <a href="#" class="btn">Event Reports(TODO)</a>
    <a href="#" class="btn">Edit Events (TODO)</a>
    <a href="logout.php" class="btn">Logout</a>

    <div class="form-container" style="margin-top: 30px;">
        <h2>Create Event</h2>
        <form action="create_event.php" method="POST">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" required>
            </div>
            <div class="form-group">
                <label for="time">Time:</label>
                <input type="time" id="time" name="time" required>
            </div>
            <div class="form-group">
                <label for="desc">Description:</label>
                <input type="text" id="desc" name="desc" required>
            </div>
            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" id="price" name="price" required>
            </div>
            <button type="submit" class="btn">Create Event</button>
        </form>
    </div>
</div>

</body>
</html>

