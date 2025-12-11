<?php
// Function to sanitize input data
function sanitize($data) {
    global $conn;
    if ($conn) {
        return mysqli_real_escape_string($conn, trim(htmlspecialchars($data)));
    }
    return trim(htmlspecialchars($data));
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function isTechnician() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'technician';
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'customer';
}

// Function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Function to generate ticket code
function generateTicketCode($request_id) {
    return "TKT-" . date('Ymd') . "-" . str_pad($request_id, 4, '0', STR_PAD_LEFT);
}

// Function to display alert messages
function showAlert() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert error">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
}
?>