<?php
declare(strict_types=1);

function openDatabase(string $databaseName): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Keep connection credentials in the server environment, not in the project files.
    $host = getenv('REALHOMES_DB_HOST') ?: 'localhost';
    $username = getenv('REALHOMES_DB_USER') ?: 'root';
    $password = getenv('REALHOMES_DB_PASSWORD');
    $password = $password === false ? '' : $password;

    $connection = new mysqli($host, $username, $password, $databaseName);
    $connection->set_charset('utf8mb4');

    return $connection;
}

function requestWantsJson(): bool
{
    return strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
}

function respond(
    bool $success,
    string $message,
    int $status = 200,
    array $data = [],
    string $returnUrl = '../HTML/HomePage.html'
): void {
    http_response_code($status);

    if (requestWantsJson()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            array_merge(['success' => $success, 'message' => $message], $data),
            JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    $escapedMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $escapedReturnUrl = htmlspecialchars($returnUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $heading = $success ? 'Request received' : 'Request could not be completed';
    $stylePath = '../HTML/css/style.css';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?> | Real Homes</title>
        <link rel="stylesheet" href="<?= htmlspecialchars($stylePath, ENT_QUOTES, 'UTF-8') ?>">
    </head>
    <body>
        <main style="width: min(100% - 32px, 680px); margin: 10vh auto; padding: 32px; background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; box-shadow: 0 16px 36px -4px rgba(15, 23, 42, .12);">
            <h1><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
            <p style="margin: 16px 0 24px; color: #475569;"><?= $escapedMessage ?></p>
            <a class="btn btn-primary" href="<?= $escapedReturnUrl ?>">Return to Real Homes</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

function requiredPostString(string $field, string $label, int $maxLength): string
{
    $value = $_POST[$field] ?? null;
    if (!is_string($value)) {
        throw new InvalidArgumentException("$label is required.");
    }

    $value = trim($value);
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($value === '' || $length > $maxLength) {
        throw new InvalidArgumentException("$label is required and must be no longer than $maxLength characters.");
    }

    return $value;
}
