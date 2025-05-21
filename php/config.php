<?php
// Database configuration
$mysql_host = 'localhost'; 
$mysql_user = 'root'; // Change to your MySQL username
$mysql_password = ''; // Change to your MySQL password
$mysql_db = 'user_auth';

// MongoDB configuration
$mongo_host = 'localhost';
$mongo_port = 27017;
$mongo_db = 'user_profiles';

// Redis configuration
$redis_host = '127.0.0.1';
$redis_port = 6379;

// Connect to MySQL
try {
    $mysql_conn = new PDO("mysql:host=$mysql_host;dbname=$mysql_db", $mysql_user, $mysql_password);
    $mysql_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log('MySQL Connection Error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed. Please try again later.']);
    exit;
}

// Connect to MongoDB - with improved error handling and connection verification
try {
    // Check if the MongoDB PHP extension is loaded
    if (!extension_loaded('mongodb')) {
        error_log('MongoDB PHP extension not loaded. Install it through PECL or your package manager.');
        $mongo_conn = null;
    }
    // Check if the MongoDB PHP client is available
    else if (class_exists('MongoDB\Client')) {
        // Make sure the autoload file exists
        // $autoload_path = __DIR__ . '/../vendor/autoload.php';
        $autoload_path = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload_path)) {
            error_log('MongoDB autoload file not found at: ' . $autoload_path);
            $mongo_conn = null;
        } else {
            require_once $autoload_path;
            
            // Log connection attempt
            error_log('Attempting MongoDB connection to: mongodb://' . $mongo_host . ':' . $mongo_port);
            
            // Create the connection string
            $mongo_connection_string = "mongodb://$mongo_host:$mongo_port";
            $mongo_client = new MongoDB\Client($mongo_connection_string);
            
            // Test connection by attempting to list databases
            $dbs = $mongo_client->listDatabases();
            $mongo_conn = $mongo_client->selectDatabase($mongo_db);
            
            // Log successful connection
            error_log('MongoDB connection successful to database: ' . $mongo_db);
        }
    } else {
        error_log('MongoDB PHP driver not installed. MongoDB functionality will be disabled.');
        $mongo_conn = null;
    }
} catch(Exception $e) {
    error_log('MongoDB Connection Error: ' . $e->getMessage());
    $mongo_conn = null;
}

// Connect to Redis - with proper error handling
try {
    // Check if the Redis PHP extension is loaded
    if (class_exists('Redis')) {
        $redis = new Redis();
        $redis->connect($redis_host, $redis_port);
        
        // Test connection
        if (!$redis->ping()) {
            error_log('Redis ping failed. Connection might not be working properly.');
            $redis = null;
        } else {
            error_log('Redis connection successful');
        }
    } else {
        error_log('Redis PHP extension not loaded. Redis functionality will be disabled.');
        $redis = null;
    }
} catch(Exception $e) {
    error_log('Redis Connection Error: ' . $e->getMessage());
    $redis = null;
}

// Function to validate session token
function validateSession($token) {
    global $redis;
    
    if ($redis && $redis->exists("session:$token")) {
        $user_id = $redis->get("session:$token");
        return $user_id;
    }
    
    return false;
}

// Function to create a new session
function createSession($user_id) {
    global $redis;
    
    $token = bin2hex(random_bytes(32));
    
    if ($redis) {
        $redis->set("session:$token", $user_id);
        $redis->expire("session:$token", 86400); // Expires in 24 hours
    }
    
    return $token;
}

// Initialize headers for AJAX requests (only if not already sent)
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}
?>