<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include('includes/dbconnection.php');
    
    try {
        // Begin transaction
        
        // Get the data from the POST request
        $admin_id = $_SESSION['odmsaid'];
        $name = $_POST['name'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $desc = $_POST['desc'];
        $s = print_r($_POST['ticket_types']);
        $conn->begin_transaction();
        
        // Prepare the SQL query to insert the event data
        $sql = "INSERT INTO events (event_name, event_date, event_time, description, admin_id, price_standard) 
                VALUES (?, ?, ?, ?, ?, 100.0)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $name, $date, $time, $desc, $admin_id);
            if ($stmt->execute()) {
                // Get the event ID
                $event_id = $conn->insert_id;
                
                // Process ticket types
                if (isset($_POST['ticket_types']) && is_array($_POST['ticket_types'])) {
                    foreach ($_POST['ticket_types'] as $ticket_type_data) {
                        // Only process if ticket type has a name
                        if (!empty($ticket_type_data['name'])) {
                            // Insert ticket type
                            $sql_ticket = "INSERT INTO ticket_types (ticket_type_name, event_id, created_at) 
                                          VALUES (?, ?, NOW())";
                            $stmt_ticket = $conn->prepare($sql_ticket);
                            $stmt_ticket->bind_param("si", $ticket_type_data['name'], $event_id);
                            $stmt_ticket->execute();
                            
                            // Get ticket type ID
                            $ticket_type_id = $conn->insert_id;
                            
                            // Process sections for this ticket type
                            if (isset($ticket_type_data['sections']) && is_array($ticket_type_data['sections'])) {
                                foreach ($ticket_type_data['sections'] as $section_id => $section_data) {
                                    // Only add price if checkbox is checked and price is provided
                                    if (isset($section_data['enabled']) && $section_data['enabled']=="on") {
                                        $sql_price = "INSERT INTO section_ticket_prices 
                                                     (ticket_type_id, section_id, price) 
                                                     VALUES (?, ?, ?)";
                                        $stmt_price = $conn->prepare($sql_price);
                                        $stmt_price->bind_param("isd", $ticket_type_id, $section_data['section_id'], $ticket_type_data['price']);
                                        $stmt_price->execute();
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // If no ticket types submitted, add default price (for backward compatibility)
                    if (isset($_POST['price'])) {
                        // Update event with standard price
                        $sql_update = "UPDATE events SET price_standard = ? WHERE id = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        $stmt_update->bind_param("di", $price, $event_id);
                        $stmt_update->execute();
                    }
                }
                
                // Commit the transaction
                $conn->commit();
                
                echo "<script>alert('Event created successfully.')</script>";
                echo "<script type='text/javascript'> document.location ='dashboard_event_admin.php'; </script>";
            } else {
                $conn->rollback();
                echo "Error: " . $stmt->error;
            }
        }
    } catch (Exception $e) {
        // Rollback the transaction if something failed
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
}
?>
