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
    // Handle file upload
    $image = $_FILES['propertyImage'];
    $uploadDir = "uploads/";
    $targetFile = $uploadDir . basename($image['name']);
    $uploadOk = 1;

    // Basic validation for price and location
    $location = $_POST['propertyLocation'];
    $price = $_POST['propertyPrice'];

    if ($price < 400000) {
        echo "<script>alert('Price must be at least R400,000.'); window.location.href='your_form_page.html';</script>";
        exit;
    }

    // Check if file is an image
    $check = getimagesize($image['tmp_name']);
    if ($check === false) {
        echo "<script>alert('File is not an image.'); window.location.href='your_form_page.html';</script>";
        $uploadOk = 0;
    }

    // Upload file if valid
    if ($uploadOk == 1 && move_uploaded_file($image['tmp_name'], $targetFile)) {
        // Prepare SQL statement to insert data into buy_add table
        $stmt = $conn->prepare("INSERT INTO buy_add (property_image, location, price) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $targetFile, $location, $price);

        // Execute statement and check if insertion was successful
        if ($stmt->execute()) {
            echo "<script>alert('Submission successful'); window.location.href='your_form_page.html';</script>";
        } else {
            echo "<script>alert('Error: Could not submit property details.'); window.location.href='your_form_page.html';</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Error uploading image.'); window.location.href='your_form_page.html';</script>";
    }
}

$conn->close();
?>
