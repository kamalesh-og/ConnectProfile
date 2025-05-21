<?php
// Include the configuration file
require_once 'config.php';

// Parse the incoming JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Debug logging
error_log('Register request received: ' . json_encode($data));

// Validate the required fields
if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$username = $data['username'];
$email = $data['email'];
$password = $data['password'];

// Additional input validation
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters']);
    exit;
}

try {
    // Check if username or email already exists
    $stmt = $mysql_conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username or email already exists']);
        exit;
    }

    // Start transaction
    $mysql_conn->beginTransaction();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into MySQL database
    $stmt = $mysql_conn->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$username, $email, $hashed_password]);
    $user_id = $mysql_conn->lastInsertId();
    
    // Commit MySQL transaction
    $mysql_conn->commit();
    
    // Create profile in MongoDB
    try {
        // Explicitly load MongoDB library if needed
        if (!class_exists('MongoDB\Client')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        
        // Create MongoDB client directly with explicit connection string
        $mongo_client = new MongoDB\Client("mongodb://localhost:27017");
        error_log('MongoDB client created successfully');
        
        // Select database
        $mongo_db = $mongo_client->selectDatabase("user_profiles");
        error_log('MongoDB database selected: user_profiles');
        
        // Select collection
        $profile_collection = $mongo_db->profiles;
        error_log('MongoDB collection selected: profiles');
        
        // Following the successful structure from test_mongo.php
        $profile_data = [
            'user_id' => (int)$user_id,
            'username' => $username,
            'email' => $email,
            'age' => null,
            'dob' => null,
            'contact' => null,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        error_log('Attempting to insert MongoDB document: ' . json_encode($profile_data));
        
        $insertResult = $profile_collection->insertOne($profile_data);
        
        if ($insertResult->getInsertedCount() > 0) {
            error_log('SUCCESS: MongoDB profile created for user: ' . $user_id . ', MongoDB ID: ' . $insertResult->getInsertedId());
        } else {
            error_log('FAILURE: MongoDB insertion returned 0 inserted documents');
        }
    } catch (Exception $e) {
        // Log MongoDB error but continue with registration
        error_log('CRITICAL MongoDB error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        error_log('MongoDB error trace: ' . $e->getTraceAsString());
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
    
} catch(Exception $e) {
    // Rollback transaction on error
    if ($mysql_conn->inTransaction()) {
        $mysql_conn->rollBack();
    }
    
    error_log('Registration error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()]);
}
?>