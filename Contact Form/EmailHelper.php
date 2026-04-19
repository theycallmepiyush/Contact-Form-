<?php
/**
 * Email Helper Class
 * ==================
 * Handles email sending via SMTP or mail()
 */

class EmailHelper {
    
    private $config;
    private $errors = [];
    
    public function __construct($config) {
        $this->config = $config['email'];
    }
    
    /**
     * Send email via SMTP
     */
    public function sendViaSMTP($to, $subject, $body, $from_email = null, $from_name = null) {
        if (!$this->config['enabled']) {
            return false;
        }
        
        $smtp = $this->config['smtp'];
        $from_email = $from_email ?? $this->config['from']['email'];
        $from_name = $from_name ?? $this->config['from']['name'];
        
        try {
            // Connect to SMTP server
            $connection = @fsockopen(
                ($smtp['encryption'] === 'ssl' ? 'ssl://' : '') . $smtp['host'],
                $smtp['port'],
                $errno,
                $errstr,
                30
            );
            
            if (!$connection) {
                $this->errors[] = "Could not connect to SMTP: $errstr ($errno)";
                return false;
            }
            
            // Read server response
            $this->readResponse($connection);
            
            // Send EHLO
            $this->sendCommand($connection, "EHLO localhost\r\n");
            $this->readResponse($connection);
            
            // Start TLS if needed
            if ($smtp['encryption'] === 'tls') {
                $this->sendCommand($connection, "STARTTLS\r\n");
                $response = $this->readResponse($connection);
                stream_context_set_params($connection, ['ssl' => ['allow_self_signed' => true]]);
                stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                // Send EHLO again after TLS
                $this->sendCommand($connection, "EHLO localhost\r\n");
                $this->readResponse($connection);
            }
            
            // Authenticate
            $this->sendCommand($connection, "AUTH LOGIN\r\n");
            $this->readResponse($connection);
            
            $this->sendCommand($connection, base64_encode($smtp['username']) . "\r\n");
            $this->readResponse($connection);
            
            $this->sendCommand($connection, base64_encode($smtp['password']) . "\r\n");
            $response = $this->readResponse($connection);
            
            if (strpos($response, '235') === false) {
                $this->errors[] = "SMTP Authentication failed: $response";
                fclose($connection);
                return false;
            }
            
            // Send email
            $this->sendCommand($connection, "MAIL FROM:<{$from_email}>\r\n");
            $this->readResponse($connection);
            
            // Handle multiple recipients
            $recipients = is_array($to) ? $to : [$to];
            foreach ($recipients as $recipient) {
                $this->sendCommand($connection, "RCPT TO:<{$recipient}>\r\n");
                $this->readResponse($connection);
            }
            
            // Send message data
            $this->sendCommand($connection, "DATA\r\n");
            $this->readResponse($connection);
            
            // Build email headers
            $headers = "From: {$from_name} <{$from_email}>\r\n";
            $headers .= "To: " . implode(", ", $recipients) . "\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            
            // Send headers and body
            $this->sendCommand($connection, "{$headers}{$body}\r\n.\r\n");
            $this->readResponse($connection);
            
            // Quit
            $this->sendCommand($connection, "QUIT\r\n");
            fclose($connection);
            
            return true;
            
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Send email via mail() function
     */
    public function sendViaMail($to, $subject, $body, $from_email = null, $from_name = null) {
        if (!$this->config['enabled']) {
            return false;
        }
        
        $from_email = $from_email ?? $this->config['from']['email'];
        $from_name = $from_name ?? $this->config['from']['name'];
        
        $headers = "From: {$from_name} <{$from_email}>\r\n";
        $headers .= "Reply-To: {$from_email}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return @mail($to, $subject, $body, $headers);
    }
    
    /**
     * Send email (uses configured method)
     */
    public function send($to, $subject, $body, $from_email = null, $from_name = null) {
        if (!$this->config['enabled']) {
            return false;
        }
        
        if ($this->config['method'] === 'smtp') {
            return $this->sendViaSMTP($to, $subject, $body, $from_email, $from_name);
        } else {
            return $this->sendViaMail($to, $subject, $body, $from_email, $from_name);
        }
    }
    
    /**
     * Send command to SMTP server
     */
    private function sendCommand(&$connection, $command) {
        fwrite($connection, $command);
    }
    
    /**
     * Read response from SMTP server
     */
    private function readResponse(&$connection) {
        $response = '';
        while ($line = fgets($connection, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }
    
    /**
     * Get errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get last error
     */
    public function getLastError() {
        return end($this->errors) ?: null;
    }
}
?>