<?php
/**
 * submit.php — SupportDesk Contact Form Backend (FIXED)
 * ======================================================
 * FIXES applied:
 *  - DB save is now INDEPENDENT of mail()
 *  - Success is based on DB insert, not email delivery
 *  - Debug mode shows real errors on localhost
 *  - mail() failure no longer blocks the success response
 */
 
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/EmailHelper.php';

$config = require __DIR__ . '/config.php';
$debug_mode = $config['debug'];

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Extract database config
$db_config = $config['database'];
$db_host = $db_config['host'];
$db_name = $db_config['name'];
$db_user = $db_config['user'];
$db_pass = $db_config['password'];
 
 
/* ── SANITIZE INPUT ──────────────────────────────────────────── */
$fname    = trim((string) filter_input(INPUT_POST, 'fname',    FILTER_SANITIZE_SPECIAL_CHARS));
$lname    = trim((string) filter_input(INPUT_POST, 'lname',    FILTER_SANITIZE_SPECIAL_CHARS));
$email    = trim((string) filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL));
$phone    = trim((string) filter_input(INPUT_POST, 'phone',    FILTER_SANITIZE_SPECIAL_CHARS));
$category = trim((string) filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS));
$priority = trim((string) filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_SPECIAL_CHARS));
$subject  = trim((string) filter_input(INPUT_POST, 'subject',  FILTER_SANITIZE_SPECIAL_CHARS));
$message  = trim((string) filter_input(INPUT_POST, 'message',  FILTER_SANITIZE_SPECIAL_CHARS));
$orderId  = trim((string) filter_input(INPUT_POST, 'orderId',  FILTER_SANITIZE_SPECIAL_CHARS));
 
 
/* ── VALIDATION ──────────────────────────────────────────────── */
$errors = [];
 
if (empty($fname) || strlen($fname) < 2)
    $errors[] = 'First name is required (min 2 characters).';
if (empty($lname) || strlen($lname) < 2)
    $errors[] = 'Last name is required (min 2 characters).';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = 'A valid email address is required.';
if (!empty($phone) && !preg_match('/^[\d\s\+\-\(\)]{7,15}$/', $phone))
    $errors[] = 'Phone number format is invalid.';
 
$allowed_categories = ['Billing & Payments','Account Access','Technical Issue','Product Inquiry','Shipping & Delivery','Returns & Refunds','Other'];
if (empty($category) || !in_array($category, $allowed_categories, true))
    $errors[] = 'Please select a valid issue category.';
 
$allowed_priorities = ['Low','Medium','High','Critical'];
if (empty($priority) || !in_array($priority, $allowed_priorities, true))
    $errors[] = 'Please select a valid priority level.';
 
if (empty($subject) || strlen($subject) < 5)
    $errors[] = 'A subject is required (min 5 characters).';
if (empty($message) || strlen($message) < 20)
    $errors[] = 'A description is required (min 20 characters).';
elseif (strlen($message) > 800)
    $errors[] = 'Description must not exceed 800 characters.';
 
if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}
 
 
/* ── PREPARE SHARED DATA ─────────────────────────────────────── */
$full_name     = htmlspecialchars("{$fname} {$lname}", ENT_QUOTES, 'UTF-8');
$phone_display = !empty($phone)   ? $phone   : 'Not provided';
$order_display = !empty($orderId) ? $orderId : 'Not provided';
$timestamp     = date('Y-m-d H:i:s');
$ticket_id     = 'SUP-' . mt_rand(100000, 999999);
 
 
/* ══════════════════════════════════════════════════════════════
   STEP 1 — SAVE TO DATABASE  ← most important step
   Success response is based on this, NOT on email.
   ══════════════════════════════════════════════════════════════ */
$db_saved = false;
try {
 
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
 
    $stmt = $pdo->prepare("
        INSERT INTO support_tickets
            (ticket_id, fname, lname, email, phone, category, priority, subject, message, order_id, created_at)
        VALUES
            (:ticket_id, :fname, :lname, :email, :phone, :category, :priority, :subject, :message, :order_id, NOW())
    ");
 
    $stmt->execute([
        ':ticket_id' => $ticket_id,
        ':fname'     => $fname,
        ':lname'     => $lname,
        ':email'     => $email,
        ':phone'     => !empty($phone)   ? $phone   : null,
        ':category'  => $category,
        ':priority'  => $priority,
        ':subject'   => $subject,
        ':message'   => $message,
        ':order_id'  => !empty($orderId) ? $orderId : null,
    ]);
 
    $db_saved = true;
 
} catch (PDOException $e) {
 
    // If database isn't set up, log to file instead (temporary fallback)
    $log_entry = "[{$timestamp}] TICKET: {$ticket_id} | NAME: {$full_name} | EMAIL: {$email} | CATEGORY: {$category} | PRIORITY: {$priority} | SUBJECT: {$subject} | MESSAGE: {$message}\n";
    if ($config['log_enabled'] && file_put_contents($config['log_file'], $log_entry, FILE_APPEND | LOCK_EX)) {
        $db_saved = true;
    }
 
    // Show real error in debug mode so you can fix it
    if ($debug_mode && !$db_saved) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '[DEBUG] Database error: ' . $e->getMessage(),
        ]);
        exit;
    }
 
    if (!$db_saved) {
        error_log('[SupportDesk] DB error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Could not save your request. Please try again.',
        ]);
        exit;
    }
}
 
 
/* ══════════════════════════════════════════════════════════════
   STEP 2 — SEND EMAILS (optional — won't block success)
   Uses configured SMTP or mail() method
   ══════════════════════════════════════════════════════════════ */
$email_helper = new EmailHelper($config);
$email_config = $config['email'];

// Prepare email content
$priority_flag = ($priority === 'Critical') ? '[CRITICAL] ' : (($priority === 'High') ? '[HIGH] ' : '');
$email_subject = "[{$email_config['company_name']}] {$priority_flag}Support: {$subject}";
$divider = str_repeat('-', 48);

// Email to support team
$support_body = "{$email_config['company_name']} - New Support Ticket: {$ticket_id}\n\n";
$support_body .= "{$divider}\n";
$support_body .= "  Name      : {$full_name}\n";
$support_body .= "  Email     : {$email}\n";
$support_body .= "  Phone     : {$phone_display}\n";
$support_body .= "{$divider}\n";
$support_body .= "  Category  : {$category}\n";
$support_body .= "  Priority  : {$priority}\n";
$support_body .= "  Subject   : {$subject}\n";
$support_body .= "  Order/Ref : {$order_display}\n";
$support_body .= "{$divider}\n";
$support_body .= "Description:\n{$message}\n\n";
$support_body .= "{$divider}\n";
$support_body .= "Submitted: {$timestamp}\n";

// Send to support team
$email_sent_to_support = $email_helper->send(
    $email_config['support_email'],
    $email_subject,
    $support_body,
    $email_config['from']['email'],
    $email_config['from']['name']
);

// Confirmation email to user
$user_subject = "[{$email_config['company_name']}] Your Support Ticket Confirmation: {$ticket_id}";
$user_body = "Thank you for contacting {$email_config['company_name']} Support!\n\n";
$user_body .= "Your ticket has been submitted successfully.\n\n";
$user_body .= "Ticket ID: {$ticket_id}\n";
$user_body .= "Submitted: {$timestamp}\n\n";
$user_body .= "We will review your request and respond within 1 business day.\n";
$user_body .= "For urgent issues, please call our helpline.\n\n";
$user_body .= "Your Details:\n";
$user_body .= "Name: {$full_name}\n";
$user_body .= "Category: {$category}\n";
$user_body .= "Priority: {$priority}\n";
$user_body .= "Subject: {$subject}\n\n";
$user_body .= "Description:\n{$message}\n\n";
$user_body .= "If you have any questions, reply to this email.\n\n";
$user_body .= "Best regards,\n{$email_config['company_name']} Support Team\n";

$email_sent_to_user = $email_helper->send(
    $email,
    $user_subject,
    $user_body,
    $email_config['from']['email'],
    $email_config['from']['name']
);
 
 
/* ══════════════════════════════════════════════════════════════
   STEP 3 — LOG TO FILE (backup record)
   ══════════════════════════════════════════════════════════════ */
if ($config['logging']['enabled']) {
    $log_line = implode(' | ', [
        $timestamp,
        $ticket_id,
        $full_name,
        $email,
        $category,
        $priority,
        $subject,
        str_replace(["\n", "\r"], ' ', $message),
    ]) . "\n";
 
    file_put_contents($config['logging']['file'], $log_line, FILE_APPEND | LOCK_EX);
}
 
 
/* ══════════════════════════════════════════════════════════════
   STEP 4 — RESPOND TO BROWSER
   Success = database was saved. Email is secondary.
   ══════════════════════════════════════════════════════════════ */
echo json_encode([
    'success'              => true,
    'message'              => 'Your support request has been submitted successfully.',
    'ticket'               => $ticket_id,
    'email_sent_to_support' => $email_sent_to_support,
    'email_sent_to_user'   => $email_sent_to_user,
    'email_method'         => $email_config['method'],
    'debug_info'           => $debug_mode ? [
        'email_errors' => $email_helper->getErrors(),
        'email_enabled' => $email_config['enabled'],
    ] : null,
]);
?>
 