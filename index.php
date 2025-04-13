<?php
// Start session to track user login state
session_start();
// error_reporting(0);
include('includes/dbconnection.php');
$sql = "SELECT * FROM events";
$query = $dbh->prepare($sql);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
// $events = [
//     [
//         'id' => 1,
//         'name' => 'Music Concert',
//         'date' => '2025-04-10',
//         'location' => 'Concert Hall, City',
//         'description' => 'Join us for an evening of live music.'
//     ],
//     [
//         'id' => 2,
//         'name' => 'Art Exhibition',
//         'date' => '2025-05-05',
//         'location' => 'Art Gallery, City',
//         'description' => 'Explore modern art pieces from local artists.'
//     ],
//     [
//         'id' => 3,
//         'name' => 'Tech Conference',
//         'date' => '2025-06-20',
//         'location' => 'Convention Center, City',
//         'description' => 'A gathering of technology enthusiasts and professionals.'
//     ]
// ];
?>

<!DOCTYPE html>
  <html lang="en">
<head>
    <link rel="shortcut icon" href="assets/images/favicon.jpg" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HELP Events</title>
    <!-- Optional: Bootstrap CSS for styling -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="/hevent/index.php">HELP Events</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
            </ul>
        </div>
    </nav>
    
    <!-- Main Content Area -->
    <div class="container mt-5">
        <!-- Display List of Events -->
        <?php 
        include('includes/dbconnection.php');
        $sql = "SELECT * FROM events";
        $query = $dbh->prepare($sql);
        $query->execute();
        $events=$query->fetchAll(PDO::FETCH_OBJ);
        $x = $query->rowCount();
        ?>
        <?php if ($query->rowCount() == 0): ?>
            <div class="list-group">There is nothing going on right now...</div>
        <?php else: ?>
        <h1>Current events</h1>
        <p>Discover upcoming events taking place at HELP University's very own Auditorium!</p>
        <div class="row">
        <?php foreach($events as $event): ?>
        <?php 
        $tstr = $event->event_date . ' ' . $event->event_time;
        $ts = strtotime($tstr);
        $microtime = microtime(true);

        // Convert to milliseconds
        $milliseconds = round($microtime);
        if ($ts < $milliseconds) {
          continue;
        } else {
        ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($event->event_name); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($event->description); ?></p>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Time:</strong> <?php echo htmlspecialchars($event->event_time); ?></li>
                            <li class="list-group-item"><strong>Date:</strong> <?php echo htmlspecialchars($event->event_date); ?></li>
                        </ul>
            <div class="card-footer">
              <button class="btn btn-primary btn-block event-seating" type="submit" event-id="<?php echo $event->event_id ?>">View Details</button>
                        </div>
                    </div>
                </div>
        <?php } ?>
        <?php endforeach; ?>
        <script>
// Add event listener for buttons with class 'event-seating'
document.querySelectorAll('.event-seating').forEach(function(button) {
    button.addEventListener('click', function() {
        let event_id = this.getAttribute('event-id'); // Get event_id from the data attribute
        let baseUrl = window.location.protocol + "//" + window.location.hostname + "/hevent/seating.php?event_id=" + event_id;
        window.location.href = baseUrl; // Redirect to the URL with event_id
    });
});
</script>
        <?php endif; ?>
        </div>
    </div>
    
    <!-- Optional: Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
</body>
</html>
