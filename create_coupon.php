<?php 
include('includes/checklogin.php');
check_login("dashboard_event_admin");
include('includes/dbconnection.php');

// Fetch available sections from database
$aa = $_SESSION["odmsaid"];
$sections = [];
$sql = "SELECT * FROM sections";
$query = $dbh->prepare($sql);
$query->execute();
$sections=$query->fetchAll(PDO::FETCH_OBJ);

$esql = "SELECT * FROM events WHERE admin_id = $aa";
$equery = $dbh->prepare($esql);
$equery->execute();
$events=$equery->fetchAll(PDO::FETCH_OBJ);
if(isset($_POST['create_coupon'])) {
    try {
        $coupon_code = $_POST['coupon_code'];
        $discount_type = $_POST['discount_type'];
        $discount_value = $_POST['discount_value'];
        $event_id = $_POST['event_id'];
        
        $sql = "INSERT INTO coupons (coupon_code, discount_type, discount_value, event_id) VALUES (:coupon_code, :discount_type, :discount_value, :event_id)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':coupon_code', $coupon_code, PDO::PARAM_STR);
        $query->bindParam(':discount_type', $discount_type, PDO::PARAM_STR);
        $query->bindParam(':discount_value', $discount_value, PDO::PARAM_STR);
        $query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $query->execute();
        
        $success_msg = "Coupon created successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error creating coupon: " . $e->getMessage();
    }
}
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
            cursor: pointer;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .form-group {
            margin: 10px 0;
        }
        input[type="text"], input[type="email"], input[type="number"], input[type="password"], 
        select, input[type="date"], input[type="time"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .ticket-type-container {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .ticket-sections {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #ccc;
        }
        .section-price {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .section-price label {
            min-width: 120px;
        }
        .section-price input {
            width: 100px;
        }
        .add-ticket-type {
            background-color: #e7f3ff;
            border: 1px dashed #2196F3;
            margin-bottom: 15px;
        }
        .remove-btn {
            background-color: #f44336;
            float: right;
        }
        .remove-btn:hover {
            background-color: #d32f2f;
        }
        #ticket-types-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
      <h1>Event Organizer Dashboard</h1>

    <a href="./dashboard_test.php" class="btn">Create Event</a>
    <a href="#" class="btn">Event Reports(TODO)</a>
    <a href="#" class="btn">Edit Events (TODO)</a>
    <a href="logout.php" class="btn">Logout</a>

    <div class="form-container" style="margin-top: 30px;">
<div class="coupon-form">
        <h2>Create Coupon</h2>
        
        <?php if(isset($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-group">
                <label for="coupon_code">Coupon Code:</label>
                <input type="text" id="coupon_code" name="coupon_code" required placeholder="e.g., SUMMER2025">
            </div>
            
            <div class="form-group">
                <label for="discount_type">Discount Type:</label>
                <select id="discount_type" name="discount_type" required>
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="discount_value">Discount Value:</label>
                <input type="number" id="discount_value" name="discount_value" required step="0.01" min="0" placeholder="e.g., 10 (for 10% or $10)">
            </div>
            
            <div class="form-group">
                <label for="event_id">Event:</label>
                <select id="event_id" name="event_id" required>
                    <option value="">Select Event</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event->event_id; ?>"><?php echo htmlspecialchars($event->event_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" name="create_coupon" class="btn" style="margin-top: 20px; display: block; width: 100%;">Create Coupon</button>
        </form>
    </div>
    </div>
</div>

<script>
    let ticketTypeCounter = 1;
    
    function addTicketType() {
        // Clone the template
        const template = document.getElementById('ticket-type-template-0');
        const clone = template.cloneNode(true);
        
        // Update IDs and names
        clone.id = 'ticket-type-template-' + ticketTypeCounter;
        
        // Update form elements inside the clone
        const inputs = clone.querySelectorAll('input');
        inputs.forEach(input => {
            // Update the input name attribute to use the new counter
            if (input.name) {
                input.name = input.name.replace('[0]', '[' + ticketTypeCounter + ']');
            }
            
            // Clear any values
            if (input.type === 'text' || input.type === 'number') {
                input.value = '';
            }
            
            // Uncheck checkboxes
            if (input.type === 'checkbox') {
                input.checked = false;
            }
        });
        
        // Append the clone
        document.getElementById('ticket-types-container').appendChild(clone);
        
        // Increment counter for next ticket type
        ticketTypeCounter++;
    }
    
    function removeTicketType(button) {
        // Get the parent container and remove it
        const container = button.closest('.ticket-type-container');
        
        // Make sure we don't remove the last one
        const allContainers = document.querySelectorAll('.ticket-type-container');
        if (allContainers.length > 1) {
            container.remove();
        } else {
            alert('You need at least one ticket type.');
        }
    }
</script>

</body>
</html>
