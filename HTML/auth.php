<?php
declare(strict_types=1);

require_once __DIR__ . '/../PHP/database.php';

function authResponse(bool $success, string $message, int $status): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['success' => $success, 'message' => $message],
        JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    authResponse(false, 'Send a login or registration request.', 405);
}

$action = $_POST['action'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!is_string($action) || !is_string($username) || !is_string($password)) {
    authResponse(false, 'Enter a valid username and password.', 422);
}

$username = trim($username);
if ($username === '' || strlen($username) > 50 || $password === '' || strlen($password) > 4096) {
    authResponse(false, 'Enter a valid username and password.', 422);
}

if ($action === 'register' &&
    (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username) || strlen($password) < 8)) {
    authResponse(
        false,
        'Use a 3–50 character username and a password with at least 8 characters.',
        422
    );
}

if (!in_array($action, ['login', 'register'], true)) {
    authResponse(false, 'Choose a valid account action.', 422);
}

try {
    $connection = openDatabase(getenv('AUTH_DATABASE') ?: 'userid_db');

    if ($action === 'register') {
        // Store only a one-way hash; the submitted password is never written to the database.
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $connection->prepare(
            'INSERT INTO users (username, password) VALUES (?, ?)'
        );
        $statement->bind_param('ss', $username, $passwordHash);
        $statement->execute();
        $statement->close();
        $connection->close();

        authResponse(true, 'Registration successful. You can now sign in.', 201);
    }

    $statement = $connection->prepare('SELECT password FROM users WHERE username = ?');
    $statement->bind_param('s', $username);
    $statement->execute();
    $statement->bind_result($passwordHash);
    $userExists = $statement->fetch();
    $statement->close();
    $connection->close();

    if (!$userExists || !password_verify($password, $passwordHash)) {
        authResponse(false, 'Username or password is incorrect.', 401);
    }

    authResponse(true, 'Login successful.', 200);
} catch (mysqli_sql_exception $exception) {
    if ($action === 'register' && (int) $exception->getCode() === 1062) {
        authResponse(false, 'That username is already registered.', 409);
    }

    error_log('Account request failed: ' . $exception->getMessage());
    authResponse(false, 'We could not process the account request. Please try again later.', 500);
} catch (Throwable $exception) {
    error_log('Account request failed: ' . $exception->getMessage());
    authResponse(false, 'We could not process the account request. Please try again later.', 500);
}
