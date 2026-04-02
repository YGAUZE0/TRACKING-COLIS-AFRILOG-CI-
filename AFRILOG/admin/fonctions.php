<?php
function cleanInput($data) {
    return htmlspecialchars(trim($data));
}

function checkAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: login.php');
        exit();
    }
}

function showError($message) {
    echo "<div class='alert alert-danger'>$message</div>";
}

function showSuccess($message) {
    echo "<div class='alert alert-success'>$message</div>";
}
?>