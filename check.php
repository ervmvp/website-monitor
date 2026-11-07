<?php
declare(strict_types=1);

// Prevent PHP errors from being displayed as HTML.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

error_reporting(E_ALL);

// Start output buffering.
ob_start();

// Always return JSON.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Convert PHP warnings into exceptions.
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException(
        $message,
        0,
        $severity,
        $file,
        $line
    );
});

try {

    require_once __DIR__ . '/includes/functions.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);

        throw new RuntimeException(
            'Only POST requests are allowed.'
        );
    }

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {
        http_response_code(400);

        throw new InvalidArgumentException(
            'Invalid JSON request.'
        );
    }

    $url = $input['url'] ?? '';

    if (!is_string($url) || trim($url) === '') {
        http_response_code(400);

        throw new InvalidArgumentException(
            'Please enter a website URL.'
        );
    }

    $result = checkWebsite($url);

    $response = [
        'success' => true,
        'data' => $result
    ];

    http_response_code(200);

} catch (Throwable $exception) {

    // Log the actual error for debugging.
    error_log(
        'PulseCheck: ' .
        $exception->getMessage() .
        ' in ' .
        $exception->getFile() .
        ':' .
        $exception->getLine()
    );

    // Return a generic message to the browser.
    if ($exception instanceof InvalidArgumentException) {
        http_response_code(400);

        $message = $exception->getMessage();

    } else {
        http_response_code(500);

        $message = 'A server error occurred. Check the PHP error log.';
    }

    $response = [
        'success' => false,
        'message' => $message
    ];

} finally {
    restore_error_handler();
}

// Remove any accidental HTML or PHP output.
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Output valid JSON.
echo json_encode(
    $response,
    JSON_INVALID_UTF8_SUBSTITUTE
);

exit;
