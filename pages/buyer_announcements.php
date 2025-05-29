<?php
// Fetch all announcements
$announcements = $pdo->query('SELECT * FROM announcements WHERE seller_id IS NULL AND stall_id IS NULL ORDER BY created_at DESC')->fetchAll(); 