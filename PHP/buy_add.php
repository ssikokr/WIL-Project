<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/upload_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Submit the property form to add a listing.', 405, [], '../HTML/BuyPage.html');
}

$imagePath = null;

try {
    $title = requiredPostString('propertyTitle', 'Property title', 180);
    $description = requiredPostString('propertyDescription', 'Property description', 10000);
    $location = requiredPostString('propertyLocation', 'Property location', 80);
    $allowedLocations = ['Sandton', 'Ruimsig', 'Krugersdorp', 'city'];
    if (!in_array($location, $allowedLocations, true)) {
        throw new InvalidArgumentException('Choose a valid property location.');
    }

    $rawPrice = $_POST['propertyPrice'] ?? '';
    if (!is_string($rawPrice) || !is_numeric($rawPrice) || (float) $rawPrice < 400000) {
        throw new InvalidArgumentException('The asking price must be at least R400,000.');
    }
    $price = (float) $rawPrice;
    if (!is_finite($price)) {
        throw new InvalidArgumentException('Enter a valid asking price.');
    }

    $rawBedrooms = $_POST['bedrooms'] ?? null;
    $rawBathrooms = $_POST['bathrooms'] ?? null;
    $rawGarages = $_POST['garages'] ?? null;
    $bedrooms = filter_var($rawBedrooms, FILTER_VALIDATE_INT);
    $bathrooms = filter_var($rawBathrooms, FILTER_VALIDATE_INT);
    $garages = filter_var($rawGarages, FILTER_VALIDATE_INT);

    if ($bedrooms === false || $bedrooms < 1 || $bedrooms > 15 ||
        $bathrooms === false || $bathrooms < 1 || $bathrooms > 15 ||
        $garages === false || $garages < 0 || $garages > 10) {
        throw new InvalidArgumentException('Enter valid bedroom, bathroom, and garage counts.');
    }

    $imagePath = saveUploadedImage($_FILES['propertyImage'] ?? [], 'sales');
    $connection = openDatabase(getenv('REALHOMES_DATABASE') ?: 'realhomes');
    $statement = $connection->prepare(
        'INSERT INTO sale_listings (title, description, location, price, bedrooms, bathrooms, garages, image_path)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->bind_param(
        'sssdiiis',
        $title,
        $description,
        $location,
        $price,
        $bedrooms,
        $bathrooms,
        $garages,
        $imagePath
    );
    $statement->execute();
    $listingId = $connection->insert_id;
    $statement->close();
    $connection->close();

    respond(
        true,
        'Your property listing has been submitted.',
        201,
        [
            'listingId' => $listingId,
            'imageUrl' => '../PHP/' . $imagePath
        ],
        '../HTML/BuyPage.html'
    );
} catch (InvalidArgumentException $exception) {
    if ($imagePath !== null) {
        removeUploadedImages([$imagePath]);
    }
    respond(false, $exception->getMessage(), 422, [], '../HTML/BuyPage.html');
} catch (Throwable $exception) {
    if ($imagePath !== null) {
        removeUploadedImages([$imagePath]);
    }
    error_log('Sale listing submission failed: ' . $exception->getMessage());
    respond(
        false,
        'We could not save the property listing. Please try again later.',
        500,
        [],
        '../HTML/BuyPage.html'
    );
}
