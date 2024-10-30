<?php
// Database Connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "userid_db";

// Create the connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

// Function for user registration
function register($conn, $username, $password) {
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare statement to insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashed_password);
    
    // Execute and handle response
    if($stmt->execute()) {
        return json_encode(["success" => true, "message" => "Registration successful"]);
    } else {
        return json_encode(["success" => false, "message" => "Registration not successful"]);
    }
}

// Function for user login
function login($conn, $username, $password) {
    // Prepare statement to fetch user details
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verify password and handle response
    if($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if(password_verify($password, $row['password'])) {
            return json_encode(["success" => true, "message" => "Login successful"]);
        }
    }
    return json_encode(["success" => false, "message" => "Invalid username or password"]);
}

// Handle POST requests
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture POST data
    $action = $_POST['action'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Respond based on the action
    if($action === 'register') {
        echo register($conn, $username, $password);
    } elseif($action === 'login') {
        echo login($conn, $username, $password);
    }
}

// Close the database connection
$conn->close();
?>
