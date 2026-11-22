<?php
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    logout_user();
    session_start();  
    flash('success', 'You have been logged out.');
}
redirect('public/login.php');