<?php 
include('includes/checklogin.php');
check_login("dashboard_event_admin");
include('includes/dbconnection.php');

// Fetch available sections from database
$sections = [];
$sql = "SELECT * FROM sections";
$query = $dbh->prepare($sql);
$query->execute();
$sections=$query->fetchAll(PDO::FETCH_OBJ);
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

    <a href="./create_coupon.php" class="btn">Create Coupon</a>
    <a href="#" class="btn">Event Reports(TODO)</a>
    <a href="#" class="btn">Edit Events (TODO)</a>
    <a href="logout.php" class="btn">Logout</a>

    <div class="form-container" style="margin-top: 30px;">
        <h2>Create Event</h2>
        <form action="create_new_test.php" method="POST">
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
            
            <!-- Ticket Types and Section Prices -->
            <h3>Ticket Types</h3>
            
            <div id="ticket-types-container">
                <!-- Template for a new ticket type (initially one empty one) -->
                <div class="ticket-type-container" id="ticket-type-template-0">
                    <button type="button" class="btn remove-btn" onclick="removeTicketType(this)">Remove</button>
                    
                    <div class="form-group">
                        <label for="ticket-type-name-0">Ticket Type Name:</label>
                        <input type="text" id="ticket-type-name-0" name="ticket_types[0][name]" placeholder="e.g., Adult, Child, VIP" required>
                    </div>
                    
                    <div class="ticket-sections">
                        <h4>Section Prices</h4>
                        <?php foreach ($sections as $section): ?>
                            <div class="section-price">
                                <label>
                                    <input type="checkbox" name="ticket_types[0][sections][<?php echo $section->section_id; ?>][enabled]">
                                    <?php echo htmlspecialchars($section->section_name); ?>:
                                </label>
                                <input type="hidden" 
                                       name="ticket_types[0][sections][<?php echo $section->section_id; ?>][section_id]" 
                                       value="<?php echo $section->section_id; ?>">
                            </div>
                        <?php endforeach; ?>
                                <input type="number" 
                                       name="ticket_types[0][price]" 
                                       placeholder="Price" 
                                       step="0.01" 
                                       min="0">
                        
                        <?php if (empty($sections)): ?>
                            <p>No sections available. Please add sections first.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn" onclick="addTicketType()">Add Another Ticket Type</button>
            
            <button type="submit" class="btn" style="margin-top: 20px; display: block; width: 100%;">Create Event</button>
        </form>
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
