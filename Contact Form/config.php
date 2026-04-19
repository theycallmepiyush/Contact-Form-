<?php
/**
 * Email Configuration File
 * ========================
 * Configure your email settings here
 */

return [
    // ── EMAIL SETTINGS ──────────────────────────────────
    'email' => [
        // Enable/disable email sending
        'enabled' => true,
        
        // Use SMTP or PHP mail() function
        // Options: 'smtp' or 'mail'
        'method' => 'smtp',  // Use SMTP for Mailtrap
        
        // SMTP Configuration (if using SMTP)
        'smtp' => [
            'host'       => 'sandbox.smtp.mailtrap.io',
            'port'       => 2525,
            'username'   => 'ba1ce917c35ea3',
            'password'   => '2342a78c06ade6',
            'encryption' => 'tls',
        ],
        
        // From email (who is sending)
        'from' => [
            'email' => 'noreply@supportdesk.local',
            'name'  => 'SupportDesk',
        ],
        
        // Support team email (where to send submissions)
        'support_email' => 'support@supportdesk.local',
        
        // Company info
        'company_name' => 'SupportDesk',
    ],
    
    // ── DATABASE SETTINGS ──────────────────────────────
    'database' => [
        'host'     => 'localhost',
        'name'     => 'supportdesk',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    
    // ── LOGGING SETTINGS ───────────────────────────────
    'logging' => [
        'enabled'  => true,
        'file'     => __DIR__ . '/tickets.log',
    ],
    
    // ── DEBUG MODE ─────────────────────────────────────
    // true = shows real errors (localhost only)
    // false = hides errors from users (production)
    'debug' => true,
];
?>