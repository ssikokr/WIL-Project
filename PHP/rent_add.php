<?php
// Database connection
$host = "localhost";
$dbname = "realhomes";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $description = $_POST['propertyDescription'];
    $price = $_POST['propertyPrice'];
    $images = $_FILES['propertyImages'];

    // Basic validation
    if (empty($description) || empty($price) || $price <= 0 || count($images['name']) < 3) {
        echo "<script>alert('Please complete the form correctly.');</script>";
        exit;
    }

    // Process images
    $uploadedPaths = [];
    $uploadDir = "uploads/";

    for ($i = 0; $i < count($images['name']); $i++) {
        $targetFile = $uploadDir . basename($images['name'][$i]);
        if (move_uploaded_file($images['tmp_name'][$i], $targetFile)) {
            $uploadedPaths[] = $targetFile;
        } else {
            echo "<script>alert('Error uploading image.');</script>";
            exit;
        }
    }

    // Save data in the database
    $imagePaths = implode(",", $uploadedPaths); // Save image paths as comma-separated string
    $stmt = $conn->prepare("INSERT INTO rentals (description, price, image_paths) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $description, $price, $imagePaths);

    if ($stmt->execute()) {
        // Show a popup message for successful submission
        echo "<script>alert('Submission successful');</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

$conn->close();
?>
