<?php
require __DIR__ . '/../includes/bootstrap.php';
redirect(current_user() ? home_for(current_user()) : 'public/login.php');
