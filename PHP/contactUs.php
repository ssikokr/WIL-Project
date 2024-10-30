<?php
// Database configuration
$servername = "localhost";
$username = "root"; // Default XAMPP user
$password = ""; // Default XAMPP password
$dbname = "newsletter_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input to prevent SQL injection and XSS
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');

    // Check if the email is valid
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Prepare and bind the SQL statement
        $stmt = $conn->prepare("INSERT INTO contact_messages (email, message) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $message);

        // Execute the query
        if ($stmt->execute()) {
            // Success, redirect or display a success message
            $success_message = "Your message has been received!";
        } else {
            // Handle query failure
            $error_message = "Error: Could not send your message.";
        }

        $stmt->close();
    } else {
        // If email is invalid
        $error_message = "Invalid email format.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form Submission</title>
</head>
<body>
    <!-- Display success or error messages -->
    <?php if (!empty($success_message)): ?>
        <p style="color: green;"><?php echo $success_message; ?></p>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <a href="HomePage.html">Back to Home</a>
</body>
</html>
