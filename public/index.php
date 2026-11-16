<?php
    require __DIR__ . '/../config/db.php';
    $count = $pdo->query('SELECT COUNT(*) AS c FROM users') ->fetch()['c'];
    echo "Setup works. Users in database: " . (int)$count;
