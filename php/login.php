<?php
// Include the configuration file
require_once 'config.php';

// Parse the incoming JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Debug logging
error_log('Login request received: ' . json_encode($data));

// Validate the required fields
if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$username = $data['username'];
$password = $data['password'];

// Additional input validation
if (empty($username) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

try {
    // Check if user exists and verify password
    $stmt = $mysql_conn->prepare("SELECT id, username, email, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
        exit;
    }
    
    // Create session token
    $token = createSession($user['id']);
    
    // Return success response with token and user data
    echo json_encode([
        'status' => 'success', 
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ]);
    
} catch(Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Login failed: ' . $e->getMessage()]);
}
?>