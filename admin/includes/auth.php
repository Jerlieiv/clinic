<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

function getBranchId() {
    return $_SESSION['branch_id'] ?? null;
}

function isAdmin() {
    return ($_SESSION['user_type'] ?? '') === 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }
}
?>