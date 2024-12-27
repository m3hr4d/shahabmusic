<?php
$users_file = 'users.json';
$users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];

// Admin credentials
$admin_username = 'admin';
$admin_password = 'admin';

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);

// Add admin user
$users[$admin_username] = [
    'password' => $hashed_password,
    'email' => 'admin@example.com',
    'role' => 'admin',
    'suspended' => false
];

// Save to file
file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));

echo "Admin user created successfully!\n";
?>
