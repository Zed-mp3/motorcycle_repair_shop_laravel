<?php
require_once __DIR__ . '/bootstrap.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getConnection();
    
    $booking_id = generateBookingId();
    $user_id = $_SESSION['user_id'] ?? null;
    $full_name = $_POST['full_name'];
    $contact = $_POST['contact_number'];
    $motorcycle = $_POST['motorcycle_model'];
    $problem = $_POST['problem_description'];
    $service_type = $_POST['service_type'];
    $preferred_date = $_POST['preferred_date'];
    $queue_no = getQueueNumber($conn);
    
    $sql = "INSERT INTO bookings (booking_id, user_id, full_name, contact_number, motorcycle_model, problem_description, service_type, preferred_date, queue_no, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissssssi", $booking_id, $user_id, $full_name, $contact, $motorcycle, $problem, $service_type, $preferred_date, $queue_no);
    
    if ($stmt->execute()) {
        $_SESSION['last_booking_id'] = $booking_id;
        header("Location: customer/track-status.php?booking_id=" . $booking_id);
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
