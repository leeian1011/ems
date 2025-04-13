<?php
// Database connection parameters
include('includes/dbconnection.php');
// Get all active events
$events_query = "SELECT event_id, event_name, event_date, event_time FROM events WHERE is_active = TRUE ORDER BY event_date ASC";
$events_result = $conn->query($events_query);

// Default event (first active event)
$event_id = 1;
if (isset($_GET['event_id'])) {
    $event_id = (int)$_GET['event_id'];
}

// Handle form submission for booking
$booking_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_seats'])) {
    // Get customer details
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $selected_seats = $_POST['selected_seats'] ?? [];
    $ticket_type_id = $_POST['ticket_type_id'] ?? 1; // Default to first ticket type if not specified
    $event_id = $_POST['event_id'] ?? 1;
    
    if (!empty($customer_name) && !empty($customer_email) && !empty($selected_seats)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Calculate total amount
            $total_amount = 0;
            foreach ($selected_seats as $seat_id) {
                // Get seat price based on section and ticket type
                $price_query = "SELECT stp.price AS seat_price 
                               FROM seats s
                               JOIN section_ticket_prices stp ON s.section_id = stp.section_id
                               WHERE s.seat_id = $seat_id AND stp.ticket_type_id = $ticket_type_id";
                $price_result = $conn->query($price_query);
                $price_row = $price_result->fetch_assoc();
                $total_amount += $price_row['seat_price'];
            }
            
            // Create booking
            $booking_sql = "INSERT INTO bookings (event_id, customer_name, customer_email, customer_phone, payment_status, total_amount) 
                          VALUES ($event_id, '$customer_name', '$customer_email', '$customer_phone', 'completed', $total_amount)";
            $conn->query($booking_sql);
            $booking_id = $conn->insert_id;
            
            // Add booking details for each selected seat
            foreach ($selected_seats as $seat_id) {
                // Get seat price again
                $price_query = "SELECT stp.price AS seat_price 
                               FROM seats s
                               JOIN section_ticket_prices stp ON s.section_id = stp.section_id
                               WHERE s.seat_id = $seat_id AND stp.ticket_type_id = $ticket_type_id";
                $price_result = $conn->query($price_query);
                $price_row = $price_result->fetch_assoc();
                $seat_price = $price_row['seat_price'];
                
                $detail_sql = "INSERT INTO booking_details (booking_id, seat_id, price, event_id, ticket_type_id) 
                              VALUES ($booking_id, $seat_id, $seat_price, $event_id, $ticket_type_id)";
                $conn->query($detail_sql);
            }
            
            // Commit transaction
            $conn->commit();
            $booking_message = '<div class="alert alert-success">Booking successful! Your booking ID is: ' . $booking_id . '</div>';
        } catch (Exception $e) {
            // Roll back transaction in case of error
            $conn->rollback();
            $booking_message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        $booking_message = '<div class="alert alert-danger">Please fill all required fields and select at least one seat.</div>';
    }
}

// Get event details
$event_query = "SELECT * FROM events WHERE event_id = $event_id";
$event_result = $conn->query($event_query);
$event = $event_result->fetch_assoc();

// Get ticket types for this event
$ticket_types_query = "SELECT ticket_type_id, ticket_type_name FROM ticket_types WHERE event_id = $event_id";
$ticket_types_result = $conn->query($ticket_types_query);
$ticket_types = [];
while ($ticket_type = $ticket_types_result->fetch_assoc()) {
    $ticket_types[$ticket_type['ticket_type_id']] = $ticket_type;
}

// Default to first ticket type if available
$selected_ticket_type = isset($_GET['ticket_type_id']) ? (int)$_GET['ticket_type_id'] : 
                        (count($ticket_types) > 0 ? array_key_first($ticket_types) : 0);

// Get all sections to organize seats
$sections_query = "SELECT * FROM sections ORDER BY section_id";
$sections_result = $conn->query($sections_query);
$sections = [];
while ($section = $sections_result->fetch_assoc()) {
    $sections[$section['section_id']] = $section;
}

// Get seats for the selected event and ticket type - grouped by section
$seats = [];
$seats_query = "SELECT 
                    s.seat_id,
                    s.section_id,
                    s.row_number,
                    s.seat_number,
                    CASE 
                        WHEN bd.booking_detail_id IS NULL THEN 'available'
                        ELSE 'booked'
                    END AS status,
                    stp.price AS price
                FROM 
                    seats s
                JOIN
                    section_ticket_prices stp ON s.section_id = stp.section_id
                LEFT JOIN 
                    booking_details bd ON bd.seat_id = s.seat_id AND bd.event_id = $event_id
                LEFT JOIN 
                    bookings b ON bd.booking_id = b.booking_id AND b.event_id = $event_id
                WHERE
                    stp.ticket_type_id = $selected_ticket_type
                    AND s.status = 'available'
                ORDER BY 
                    s.section_id, s.row_number, s.seat_number";
                    
$seats_result = $conn->query($seats_query);

while ($seat = $seats_result->fetch_assoc()) {
    $seats[$seat['section_id']][] = $seat;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="shortcut icon" href="assets/images/favicon.jpg" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $event['event_name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stage {
            background-color: #f0f0f0;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .seat {
            width: 30px;
            height: 30px;
            margin: 3px;
            display: inline-block;
            cursor: pointer;
            text-align: center;
            line-height: 30px;
            font-size: 10px;
            border-radius: 5px;
        }
        .seat.available {
            background-color: #4CAF50;
            color: white;
        }
        .seat.booked {
            background-color: #f44336;
            color: white;
            cursor: not-allowed;
        }
        .seat.selected {
            background-color: #2196F3;
            color: white;
        }
        .section-title {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        #selected-seats-container {
            margin-top: 20px;
        }
        .section-container {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .ticket-type-selector {
            margin-bottom: 20px;
        }
    </style>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="/hevent/index.php">HELP Events</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
            </ul>
        </div>
    </nav>
    <div class="container mt-4 mb-5">
        <!-- <h1 class="text-center mb-4">Auditorium Booking System</h1> -->
        
        <h1 class="text-center mb-4"><?php echo $event['event_name'] ?></h1>
        
        <!-- Event Selection -->
        <div class="row mb-4">
            <div class="col-md-6 offset-md-3">
            </div>
        </div>
        
        <!-- Event Details -->
        <div class="row mb-4">
            <div class="col-md-6 offset-md-3">
                <div class="alert alert-info">
                    <h4><?php echo $event['event_name']; ?></h4>
                    <p>Date: <?php echo date('F d, Y', strtotime($event['event_date'])); ?><br>
                    Time: <?php echo date('h:i A', strtotime($event['event_time'])); ?></p>
                    <p><?php echo $event['description']; ?></p>
                </div>
            </div>
        </div>
        <img src="assets/images/seating.png" alt="Italian Trulli">
        
        <!-- Ticket Type Selection -->
        <div class="row mb-3">
            <div class="col-md-6 offset-md-3">
                <div class="ticket-type-selector">
                    <h4>Select Ticket Type:</h4>
                    <form id="ticket-type-form" method="get" action="">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <select name="ticket_type_id" id="ticket-type-select" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($ticket_types as $id => $ticket_type): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($selected_ticket_type == $id) ? 'selected' : ''; ?>>
                                    <?php echo $ticket_type['ticket_type_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Display booking message if exists -->
        <?php if (!empty($booking_message)): ?>
            <div class="row mb-3">
                <div class="col-md-6 offset-md-3">
                    <?php echo $booking_message; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Seating Chart -->
        <h2 class="text-center mb-3">Select Your Seats</h2>
        <div class="stage mb-4">STAGE</div>
        
        <form method="post" id="booking-form">
            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
            <input type="hidden" name="ticket_type_id" value="<?php echo $selected_ticket_type; ?>">
            <div class="row">
                <div class="col-md-8">
                    <!-- Main seating area -->
                    <div class="seating-chart">
                        <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L'] as $section_id): ?>
                            <?php if (isset($seats[$section_id])): ?>
                                <div class="section-container">
                                    <div class="section-title">Section <?php echo $section_id; ?></div>
                                    <div class="section-seats text-center">
                                        <?php foreach ($seats[$section_id] as $seat): ?>
                                            <div class="seat <?php echo $seat['status']; ?>" 
                                                 data-seat-id="<?php echo $seat['seat_id']; ?>"
                                                 data-section="<?php echo $seat['section_id']; ?>"
                                                 data-row="<?php echo $seat['row_number']; ?>"
                                                 data-seat="<?php echo $seat['seat_number']; ?>"
                                                 data-price="<?php echo $seat['price']; ?>"
                                                 title="Section <?php echo $seat['section_id']; ?>, Row <?php echo $seat['row_number']; ?>, Seat <?php echo $seat['seat_number']; ?> - $<?php echo $seat['price']; ?>">
                                                <?php echo $seat['seat_number']; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Back seating area -->
                    <div class="seating-chart mt-4">
                        <?php foreach (['AA', 'BB', 'CC', 'DD', 'EE'] as $section_id): ?>
                            <?php if (isset($seats[$section_id])): ?>
                                <div class="section-container">
                                    <div class="section-title">Section <?php echo $section_id; ?></div>
                                    <div class="section-seats text-center">
                                        <?php foreach ($seats[$section_id] as $seat): ?>
                                            <div class="seat <?php echo $seat['status']; ?>" 
                                                 data-seat-id="<?php echo $seat['seat_id']; ?>"
                                                 data-section="<?php echo $seat['section_id']; ?>"
                                                 data-row="<?php echo $seat['row_number']; ?>"
                                                 data-seat="<?php echo $seat['seat_number']; ?>"
                                                 data-price="<?php echo $seat['price']; ?>"
                                                 title="Section <?php echo $seat['section_id']; ?>, Row <?php echo $seat['row_number']; ?>, Seat <?php echo $seat['seat_number']; ?> - $<?php echo $seat['price']; ?>">
                                                <?php echo $seat['seat_number']; ?>
                                            </div>
<?php endforeach; ?>
</div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Booking Form -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            Booking Information
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="customer_email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email" required>
                            </div>
                            <div class="mb-3">
                                <label for="customer_phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="customer_phone" name="customer_phone">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ticket Type</label>
                                <div class="form-control bg-light">
                                    <?php 
                                    if (isset($ticket_types[$selected_ticket_type])) {
                                        echo $ticket_types[$selected_ticket_type]['ticket_type_name'];
                                    } else {
                                        echo 'Unknown Ticket';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Coupon Code</label>
                                <input type="text" class="form-control" id="customer_coupon" name="customer_coupon"><br></br>
                                <button name="apply_coupon" class="btn btn-primary w-100" id="apply_coupon">
                                  Apply Coupon 
                                </button>
                            </div>

                            <div id="selected-seats-container" class="mb-3">
                                <h5>Selected Seats</h5>
                                <div id="selected-seats-list" class="alert alert-info">
                                    No seats selected
                                </div>
                                <div id="total-price" class="fw-bold text-end fs-5">
                                    Total: $0.00
                                </div>
                            </div>
                            
                            <!-- Hidden input to store selected seat IDs -->
                            <div id="selected-seats-input-container"></div>
                            
                            <button type="submit" name="book_seats" class="btn btn-primary w-100" id="book-button" disabled>
                                Complete Booking
                            </button>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            Seat Legend
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="seat available me-2" style="cursor: default;">A</div>
                                <div>Available</div>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="seat booked me-2" style="cursor: default;">A</div>
                                <div>Booked</div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="seat selected me-2" style="cursor: default;">A</div>
                                <div>Selected</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        const priceElem = document.getElementById('total-price');
        const couponInputElem = document.getElementById('customer_coupon');
        const applyCouponBtn = document.getElementById('apply_coupon');
          applyCouponBtn.addEventListener('click', function(event) {
            event.preventDefault();
          let price = priceElem.innerHTML;
          let coupon_code = couponInputElem.value;
          var current_price = Number(price.split('Total: $')[1]);
          let event_id = <?php echo "$event_id"; ?>;
          let post_data = {
            event_id: event_id,
            cprice: current_price,
            coupon_code: coupon_code,
          };

          // Use fetch to send the POST request
          fetch(
              'calculate_coupon.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded', // or 'application/json' depending on your data forma  t
              },
              body: new URLSearchParams(post_data)
            })
            .then(response => {
              if (response.ok) {
                return new Promise(async (resolve) => {resolve([true, await response.text(), null])}); // Get the response as text
              } else {
                console.log('shii' + response.status);
                return new Promise(async (resolve) => {resolve([false, current_price.toFixed(2), await response.text()])}); // Get the response as text
              }
            })
            .then(data => {
                console.log('we are init ' + JSON.stringify(data));
                // Process the response from the server
                if (!data[0]) {
                  console.log('fail ' + data[2]);
                }
                document.getElementById('total-price').innerHTML = 'Total: $' + Number(data[1]).toFixed(2);
            })
            .catch(error => {
              console.log('There was an error with the request:', error);
            }); 
        });
            // Store selected seats
            const selectedSeats = new Map();
            let totalPrice = 0;
            
            // Get elements
            const selectedSeatsList = document.getElementById('selected-seats-list');
            const totalPriceElement = document.getElementById('total-price');
            const bookButton = document.getElementById('book-button');
            const selectedSeatsInputContainer = document.getElementById('selected-seats-input-container');
            
            // Add click event listener to all available seats
            document.querySelectorAll('.seat.available').forEach(seat => {
                seat.addEventListener('click', function() {
                    const seatId = this.getAttribute('data-seat-id');
                    const section = this.getAttribute('data-section');
                    const row = this.getAttribute('data-row');
                    const seatNumber = this.getAttribute('data-seat');
                    const price = parseFloat(this.getAttribute('data-price'));
                    
                    if (this.classList.contains('selected')) {
                        // Deselect seat
                        this.classList.remove('selected');
                        this.classList.add('available');
                        selectedSeats.delete(seatId);
                        totalPrice -= price;
                    } else {
                        // Select seat
                        this.classList.remove('available');
                        this.classList.add('selected');
                        selectedSeats.set(seatId, {
                            section: section,
                            row: row,
                            seatNumber: seatNumber,
                            price: price
                        });
                        totalPrice += price;
                    }
                    
                    updateSelectedSeatsDisplay();
                });
            });
            
            // Function to update the display of selected seats
            function updateSelectedSeatsDisplay() {
                // Clear previous inputs
                selectedSeatsInputContainer.innerHTML = '';
                
                if (selectedSeats.size === 0) {
                    selectedSeatsList.innerHTML = 'No seats selected';
                    bookButton.disabled = true;
                } else {
                    let html = '<ul class="mb-0">';
                    
                    // Group selected seats by section
                    const seatsBySection = new Map();
                    
                    selectedSeats.forEach((details, seatId) => {
                        if (!seatsBySection.has(details.section)) {
                            seatsBySection.set(details.section, []);
                        }
                        seatsBySection.get(details.section).push({
                            id: seatId,
                            details: details
                        });
                        
                        // Add hidden input for each selected seat
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'selected_seats[]';
                        input.value = seatId;
                        selectedSeatsInputContainer.appendChild(input);
                    });
                    
                    // Display seats grouped by section
                    seatsBySection.forEach((seats, section) => {
                        html += `<li>Section ${section}: `;
                        
                        // Sort seats by number
                        seats.sort((a, b) => parseInt(a.details.seatNumber) - parseInt(b.details.seatNumber));
                        
                        // List seat numbers
                        const seatNumbers = seats.map(seat => `#${seat.details.seatNumber} ($${seat.details.price.toFixed(2)})`);
                        html += seatNumbers.join(', ');
                        
                        html += '</li>';
                    });
                    
                    html += '</ul>';
                    selectedSeatsList.innerHTML = html;
                    bookButton.disabled = false;
                }
                
                // Update total price
                totalPriceElement.innerHTML = `Total: $${totalPrice.toFixed(2)}`;
            }
        });

    </script>
    
</body>
</html>
<?php $conn->close(); ?>
