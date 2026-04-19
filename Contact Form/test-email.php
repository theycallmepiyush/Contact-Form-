<?php
/**
 * test-email.php — Email Configuration Test Backend
 * ==================================================
 * Allows testing email configuration before deployment
 */

header('Content-Type: application/json; charset=utf-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/EmailHelper.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$required = ['method', 'from_email', 'to_email', 'subject', 'message'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Create config array based on method
    if ($input['method'] === 'smtp') {
        $required_smtp = ['host', 'port', 'username', 'password', 'encryption'];
        foreach ($required_smtp as $field) {
            if (empty($input[$field])) {
                throw new Exception("Missing SMTP field: $field");
            }
        }

        $config = [
            'email' => [
                'enabled' => true,
                'method' => 'smtp',
                'smtp' => [
                    'host'       => $input['host'],
                    'port'       => (int) $input['port'],
                    'username'   => $input['username'],
                    'password'   => $input['password'],
                    'encryption' => $input['encryption'],
                ],
                'from' => [
                    'email' => $input['from_email'],
                    'name'  => $input['from_name'] ?? 'SupportDesk',
                ],
                'support_email' => $input['to_email'],
                'company_name' => $input['from_name'] ?? 'SupportDesk',
            ]
        ];
    } else {
        $config = [
            'email' => [
                'enabled' => true,
                'method' => 'mail',
                'from' => [
                    'email' => $input['from_email'],
                    'name'  => $input['from_name'] ?? 'SupportDesk',
                ],
                'support_email' => $input['to_email'],
                'company_name' => $input['from_name'] ?? 'SupportDesk',
            ]
        ];
    }

    // Send test email
    $mailer = new EmailHelper($config);
    $sent = $mailer->send(
        $input['to_email'],
        $input['subject'],
        $input['message'],
        $input['from_email'],
        $input['from_name'] ?? 'SupportDesk'
    );

    if ($sent) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "✅ Test email sent successfully to {$input['to_email']}\n\nCheck your inbox to verify the configuration is working.",
            'debug' => [
                'method' => $input['method'],
                'from' => $input['from_email'],
                'to' => $input['to_email'],
                'timestamp' => date('Y-m-d H:i:s'),
            ]
        ]);
    } else {
        http_response_code(500);
        $errors = $mailer->getErrors();
        echo json_encode([
            'success' => false,
            'message' => "❌ Failed to send test email\n\nErrors:\n" . implode("\n", $errors ?: ['Unknown error']),
            'debug' => [
                'method' => $input['method'],
                'errors' => $errors,
            ]
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '❌ Configuration Error: ' . $e->getMessage(),
        'debug' => [
            'exception' => get_class($e),
            'error' => $e->getMessage(),
        ]
    ]);
}
?>