<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'motorcycle_repair_shop');

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Start session FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for remember me cookie on page load
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    $conn = getConnection();
    $token = $_COOKIE['remember_token'];
    $sql = "SELECT * FROM users WHERE remember_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
    }
    $conn->close();
}
?>