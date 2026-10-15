<?php
require_once __DIR__ . '/../bootstrap.php';
// No need to require database.php here as it's already included
// The session is already started in database.php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isMechanic() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'mechanic';
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../login.php');
        exit();
    }
}

function requireMechanic() {
    if (!isMechanic()) {
        header('Location: ../login.php');
        exit();
    }
}

function generateBookingId() {
    return 'BK' . date('Ymd') . rand(1000, 9999);
}

function getQueueNumber($conn) {
    $date = date('Y-m-d');
    // PostgreSQL uses ::date casting instead of the DATE() function
$sql = "SELECT MAX(queue_no) as max_queue FROM bookings WHERE created_at::date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return ($row['max_queue'] ?? 0) + 1;
}
?>
