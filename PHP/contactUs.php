<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Submit the contact form to send a message.', 405, [], '../HTML/contactUs.html');
}

try {
    $email = requiredPostString('email', 'Email address', 254);
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Enter a valid email address.');
    }

    // The selling form adds the caller and property details to this message field.
    $message = requiredPostString('message', 'Message', 10000);
    $databaseName = getenv('CONTACT_DATABASE') ?: 'newsletter_db';
    $connection = openDatabase($databaseName);
    $statement = $connection->prepare(
        'INSERT INTO contact_messages (email, message) VALUES (?, ?)'
    );
    $statement->bind_param('ss', $email, $message);
    $statement->execute();
    $statement->close();
    $connection->close();

    respond(
        true,
        'Your message has been received. The Real Homes team will be in touch.',
        200,
        [],
        '../HTML/contactUs.html'
    );
} catch (InvalidArgumentException $exception) {
    respond(false, $exception->getMessage(), 422, [], '../HTML/contactUs.html');
} catch (Throwable $exception) {
    error_log('Contact message submission failed: ' . $exception->getMessage());
    respond(
        false,
        'We could not save your message. Please try again later.',
        500,
        [],
        '../HTML/contactUs.html'
    );
}
