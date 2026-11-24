<?php
declare(strict_types=1);

function saveUploadedImage(array $file, string $category): string
{
    if (!isset($file['error'], $file['tmp_name'], $file['size']) || is_array($file['error'])) {
        throw new InvalidArgumentException('Choose a valid image file.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('The image upload did not finish. Please try again.');
    }

    if ($file['size'] < 1 || $file['size'] > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Each image must be smaller than 5 MB.');
    }

    // Check the uploaded bytes instead of trusting the browser-provided filename or MIME type.
    $imageInfo = getimagesize($file['tmp_name']);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if ($imageInfo === false || !isset($extensions[$mimeType])) {
        throw new InvalidArgumentException('Upload a JPEG, PNG, or WebP image.');
    }

    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $category;
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('The image upload directory could not be created.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    $destination = $directory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The uploaded image could not be saved.');
    }

    return 'uploads/' . $category . '/' . $filename;
}

function removeUploadedImages(array $paths): void
{
    foreach ($paths as $path) {
        $fullPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($fullPath) && !unlink($fullPath)) {
            error_log('Could not remove an uploaded image after a failed listing submission.');
        }
    }
}
