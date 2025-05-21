<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$redis_host = 'localhost';
$redis_port = 6379;

// Set response header first - before any output
header('Content-Type: application/json');

// Log file for debugging
$log_file = __DIR__ . '/profile_debug.log';

// Helper function to log messages
function logMessage($message) {
    global $log_file;
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Script started - " . $_SERVER['REQUEST_METHOD'] . " request");

// Get authorization header
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

logMessage("Auth header: " . substr($auth_header, 0, 20) . "...");

// Check for Bearer token
if (!preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    logMessage("Invalid token format");
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing token']);
    exit;
}

$token = $matches[1];
logMessage("Token extracted: " . substr($token, 0, 10) . "...");

// Connect to Redis first since we need it for token validation
try {
    logMessage("Connecting to Redis...");
    $redis = new Redis();
    $connected = $redis->connect($redis_host, $redis_port);
    if (!$connected) {
        throw new Exception("Could not connect to Redis");
    }
    logMessage("Redis connected successfully");
} catch(Exception $e) {
    logMessage("Redis connection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Redis Connection failed: ' . $e->getMessage()]);
    exit;
}

// Verify token with Redis
$user_id = $redis->get("session:$token");
logMessage("User ID from token: " . ($user_id ?: 'Not found'));

if (!$user_id) {
    logMessage("Invalid or expired token");
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
    exit;
}

// Now connect to MySQL
try {
    logMessage("Connecting to MySQL...");
    $mysql_conn = new PDO("mysql:host=$mysql_host;dbname=$mysql_db", $mysql_user, $mysql_password);
    $mysql_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logMessage("MySQL connected successfully");
} catch(PDOException $e) {
    logMessage("MySQL connection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'MySQL Connection failed: ' . $e->getMessage()]);
    exit;
}

// Connect to MongoDB
try {
    logMessage("Checking for MongoDB library...");
    if (!class_exists('MongoDB\Client')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        logMessage("MongoDB library loaded via Composer autoload");
    } else {
        logMessage("MongoDB library already available");
    }

    logMessage("Connecting to MongoDB at mongodb://$mongo_host:$mongo_port...");
    $mongo_client = new MongoDB\Client("mongodb://$mongo_host:$mongo_port");
    
    logMessage("Selecting database: $mongo_db");
    $mongo_conn = $mongo_client->selectDatabase($mongo_db);

    logMessage("MongoDB connected and database selected successfully");
} catch(Exception $e) {
    logMessage("MongoDB connection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'MongoDB Connection failed: ' . $e->getMessage()]);
    exit;
}

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];
logMessage("Processing $method request");

switch ($method) {
    case 'GET':
        // Fetch user profile
        try {
            logMessage("Fetching user profile for ID: $user_id");
            
            // Get user basic info from MySQL
            $stmt = $mysql_conn->prepare("SELECT username, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                logMessage("User not found in MySQL database");
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
                exit;
            }
            logMessage("User found in MySQL: " . json_encode($user));
            
            // Get user profile from MongoDB
            $profile_collection = $mongo_conn->selectCollection('profiles');
            $profile = $profile_collection->findOne(['user_id' => (int)$user_id]);
            logMessage("MongoDB profile data: " . ($profile ? 'Found' : 'Not found'));
            
            // Process date for output if exists
            $dob = null;
            if (isset($profile['dob'])) {
                if ($profile['dob'] instanceof MongoDB\BSON\UTCDateTime) {
                    $dob_dt = $profile['dob']->toDateTime();
                    $dob = $dob_dt->format('Y-m-d');
                    logMessage("Converted MongoDB date to: $dob");
                } else {
                    $dob = $profile['dob'];
                    logMessage("Using stored date value: $dob");
                }
            }
            
            // Combine data
            $profile_data = [
                'username' => $user['username'],
                'email' => $user['email'],
                'age' => isset($profile['age']) ? $profile['age'] : null,
                'dob' => $dob,
                'contact' => isset($profile['contact']) ? $profile['contact'] : null
            ];
            
            logMessage("Sending profile data: " . json_encode($profile_data));
            echo json_encode(['status' => 'success', 'profile' => $profile_data]);
        } catch(Exception $e) {
            logMessage("Profile fetch error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch profile: ' . $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Update user profile
        try {
            logMessage("Updating profile for user ID: $user_id");
            
            // Get POST data
            $input = file_get_contents('php://input');
            logMessage("Received input data: " . $input);
            
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                logMessage("JSON decode error: " . json_last_error_msg());
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data: ' . json_last_error_msg()]);
                exit;
            }
            
            // Process date for MongoDB if provided
            $mongo_dob = null;
            if (!empty($data['dob'])) {
                $date_timestamp = strtotime($data['dob']);
                if ($date_timestamp !== false) {
                    $mongo_dob = new MongoDB\BSON\UTCDateTime($date_timestamp * 1000);
                    logMessage("Converted date to MongoDB format");
                } else {
                    logMessage("Failed to parse date: " . $data['dob']);
                }
            }
            
            // Update profile in MongoDB
            $profile_collection = $mongo_conn->selectCollection('profiles');
            
            // Check if profile exists
            $existing_profile = $profile_collection->findOne(['user_id' => (int)$user_id]);
            logMessage("Existing profile: " . ($existing_profile ? 'Found' : 'Not found'));
            
            if ($existing_profile) {
                // Update existing profile
                $update_data = [
                    'age' => isset($data['age']) && $data['age'] !== '' ? (int)$data['age'] : null,
                    'contact' => isset($data['contact']) ? $data['contact'] : null,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                // Only add dob if it's valid
                if ($mongo_dob !== null) {
                    $update_data['dob'] = $mongo_dob;
                }
                
                logMessage("Updating profile with data: " . json_encode($update_data));
                
                $result = $profile_collection->updateOne(
                    ['user_id' => (int)$user_id],
                    ['$set' => $update_data]
                );
                
                logMessage("Update result - Modified: " . $result->getModifiedCount() . ", Matched: " . $result->getMatchedCount());
                
                if ($result->getModifiedCount() > 0 || $result->getMatchedCount() > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
                } else {
                    echo json_encode(['status' => 'success', 'message' => 'No changes were made to profile']);
                }
            } else {
                logMessage("Creating new profile");
                // Get user basic info from MySQL for new profile
                $stmt = $mysql_conn->prepare("SELECT username, email FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    logMessage("User not found in MySQL database");
                    http_response_code(404);
                    echo json_encode(['status' => 'error', 'message' => 'User not found']);
                    exit;
                }
                
                // Create new profile
                $new_profile = [
                    'user_id' => (int)$user_id,
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'age' => isset($data['age']) && $data['age'] !== '' ? (int)$data['age'] : null,
                    'contact' => isset($data['contact']) ? $data['contact'] : null,
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                // Only add dob if it's valid
                if ($mongo_dob !== null) {
                    $new_profile['dob'] = $mongo_dob;
                }
                
                logMessage("Inserting new profile: " . json_encode($new_profile));
                
                $insert_result = $profile_collection->insertOne($new_profile);
                
                if ($insert_result->getInsertedCount() > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Profile created successfully']);
                } else {
                    logMessage("Failed to insert profile");
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Failed to create profile']);
                }
            }
        } catch(Exception $e) {
            logMessage("Profile update error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update profile: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Logout - remove session from Redis
        try {
            logMessage("Logging out user, deleting token from Redis");
            $deleted = $redis->del("session:$token");
            logMessage("Redis del result: " . ($deleted ? 'Success' : 'Failed'));
            
            echo json_encode(['status' => 'success', 'message' => 'Logged out successfully']);
        } catch(Exception $e) {
            logMessage("Logout error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Logout failed: ' . $e->getMessage()]);
        }
        break;
        
    default:
        logMessage("Invalid request method: $method");
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

logMessage("Script completed");
?>