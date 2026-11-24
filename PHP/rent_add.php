<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/upload_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Submit the rental form to add a listing.', 405, [], '../HTML/RentPage.html');
}

$uploadedPaths = [];

try {
    $title = requiredPostString('propertyTitle', 'Rental title', 180);
    $description = requiredPostString('propertyDescription', 'Rental description', 10000);
    $type = requiredPostString('propertyType', 'Property type', 32);
    if (!in_array($type, ['apartment', 'house', 'studio', 'townhouse'], true)) {
        throw new InvalidArgumentException('Choose a valid rental property type.');
    }

    $rawPrice = $_POST['propertyPrice'] ?? '';
    if (!is_string($rawPrice) || !is_numeric($rawPrice) || (float) $rawPrice < 2000) {
        throw new InvalidArgumentException('Monthly rent must be at least R2,000.');
    }
    $price = (float) $rawPrice;
    if (!is_finite($price)) {
        throw new InvalidArgumentException('Enter a valid monthly rent.');
    }

    $bedrooms = filter_var($_POST['bedrooms'] ?? null, FILTER_VALIDATE_INT);
    $bathrooms = filter_var($_POST['bathrooms'] ?? null, FILTER_VALIDATE_INT);
    if ($bedrooms === false || $bedrooms < 1 || $bedrooms > 10 ||
        $bathrooms === false || $bathrooms < 1 || $bathrooms > 10) {
        throw new InvalidArgumentException('Enter valid bedroom and bathroom counts.');
    }

    $files = $_FILES['propertyImages'] ?? null;
    if (!is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
        throw new InvalidArgumentException('Upload at least one property photo.');
    }

    $fileCount = count($files['name']);
    if ($fileCount < 1 || $fileCount > 10) {
        throw new InvalidArgumentException('Upload between one and ten property photos.');
    }

    foreach ($files['name'] as $index => $unusedName) {
        $uploadedPaths[] = saveUploadedImage([
            'name' => $files['name'][$index],
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0
        ], 'rentals');
    }

    $imagePaths = json_encode($uploadedPaths, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($imagePaths === false) {
        throw new RuntimeException('The uploaded image paths could not be prepared.');
    }

    $connection = openDatabase(getenv('REALHOMES_DATABASE') ?: 'realhomes');
    $statement = $connection->prepare(
        'INSERT INTO rental_listings (title, description, property_type, price, bedrooms, bathrooms, image_paths)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->bind_param(
        'sssdiis',
        $title,
        $description,
        $type,
        $price,
        $bedrooms,
        $bathrooms,
        $imagePaths
    );
    $statement->execute();
    $listingId = $connection->insert_id;
    $statement->close();
    $connection->close();

    $imageUrls = array_map(static function (string $path): string {
        return '../PHP/' . $path;
    }, $uploadedPaths);

    respond(
        true,
        'Your rental listing has been submitted.',
        201,
        [
            'listingId' => $listingId,
            'imageUrls' => $imageUrls
        ],
        '../HTML/RentPage.html'
    );
} catch (InvalidArgumentException $exception) {
    removeUploadedImages($uploadedPaths);
    respond(false, $exception->getMessage(), 422, [], '../HTML/RentPage.html');
} catch (Throwable $exception) {
    removeUploadedImages($uploadedPaths);
    error_log('Rental listing submission failed: ' . $exception->getMessage());
    respond(
        false,
        'We could not save the rental listing. Please try again later.',
        500,
        [],
        '../HTML/RentPage.html'
    );
}
