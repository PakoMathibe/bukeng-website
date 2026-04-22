<?php
/**
 * Bukeng Waitlist Form Handler
 * Secure PHP endpoint for processing waitlist signups
 * Sends email notifications to webmail@bukeng.co.za
 */

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);

// Set JSON response header
header('Content-Type: application/json');

// Configuration
define('ADMIN_EMAIL', 'webmail@bukeng.co.za');
define('FROM_EMAIL', 'notifications@bukeng.co.za');
define('SITE_NAME', 'Bukeng');

// Rate limiting configuration
define('RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds
define('RATE_LIMIT_MAX', 5); // Max 5 submissions per IP per hour

// Function to get client IP address
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 255;
}

// Function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Function for rate limiting
function checkRateLimit($ip) {
    $rateLimitFile = sys_get_temp_dir() . '/bukeng_ratelimit_' . md5($ip);
    
    if (file_exists($rateLimitFile)) {
        $data = json_decode(file_get_contents($rateLimitFile), true);
        $timeDiff = time() - $data['timestamp'];
        
        if ($timeDiff < RATE_LIMIT_WINDOW) {
            if ($data['count'] >= RATE_LIMIT_MAX) {
                return false;
            }
            $data['count']++;
        } else {
            $data['count'] = 1;
            $data['timestamp'] = time();
        }
    } else {
        $data = ['count' => 1, 'timestamp' => time()];
    }
    
    file_put_contents($rateLimitFile, json_encode($data));
    return true;
}

// Function to send email
function sendEmail($to, $subject, $message, $replyTo = null) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . FROM_EMAIL . "\r\n";
    if ($replyTo) {
        $headers .= "Reply-To: " . $replyTo . "\r\n";
    }
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
}

// Function to format email HTML
function formatEmailHTML($data) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #000; color: #fff; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; }
            .field { margin-bottom: 15px; padding: 10px; background: #fff; border-radius: 5px; }
            .label { font-weight: bold; color: #000; }
            .value { margin-top: 5px; color: #666; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>🎉 New Waitlist Signup!</h2>
            </div>
            <div class="content">
                <div class="field">
                    <div class="label">Full Name:</div>
                    <div class="value">' . htmlspecialchars($data['full_name']) . '</div>
                </div>
                <div class="field">
                    <div class="label">Email:</div>
                    <div class="value">' . htmlspecialchars($data['email']) . '</div>
                </div>
                <div class="field">
                    <div class="label">Location:</div>
                    <div class="value">' . htmlspecialchars($data['location']) . '</div>
                </div>
                <div class="field">
                    <div class="label">Grocery Store:</div>
                    <div class="value">' . htmlspecialchars($data['grocery_store']) . '</div>
                </div>
                <div class="field">
                    <div class="label">Restaurant:</div>
                    <div class="value">' . htmlspecialchars($data['restaurant']) . '</div>
                </div>
                <div class="field">
                    <div class="label">Platform:</div>
                    <div class="value">' . htmlspecialchars($data['platform']) . '</div>
                </div>
                <div class="field">
                    <div class="label">IP Address:</div>
                    <div class="value">' . htmlspecialchars($data['ip']) . '</div>
                </div>
                <div class="field">
                    <div class="label">Timestamp:</div>
                    <div class="value">' . date('Y-m-d H:i:s') . '</div>
                </div>
            </div>
            <div class="footer">
                <p>Bukeng Waitlist System</p>
                <p>&copy; ' . date('Y') . ' Bukeng. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
}

// Main execution
try {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    
    // Honeypot check (bot protection)
    if (!empty($_POST['honeypot'])) {
        // Bot detected - pretend success but don't process
        echo json_encode(['success' => true, 'message' => 'Successfully joined waitlist!']);
        exit;
    }
    
    // Rate limiting
    $clientIP = getClientIP();
    if (!checkRateLimit($clientIP)) {
        echo json_encode(['success' => false, 'message' => 'Too many attempts. Please try again later.']);
        exit;
    }
    
    // Validate required fields
    $required = ['full_name', 'email', 'location', 'grocery_store', 'restaurant', 'platform'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
            exit;
        }
    }
    
    // Sanitize and validate inputs
    $fullName = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $location = sanitizeInput($_POST['location']);
    $groceryStore = sanitizeInput($_POST['grocery_store']);
    $restaurant = sanitizeInput($_POST['restaurant']);
    $platform = sanitizeInput($_POST['platform']);
    
    // Validate email
    if (!validateEmail($email)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }
    
    // Validate name length
    if (strlen($fullName) < 2 || strlen($fullName) > 100) {
        echo json_encode(['success' => false, 'message' => 'Name must be between 2 and 100 characters']);
        exit;
    }
    
    // Validate location
    if (strlen($location) < 2 || strlen($location) > 100) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid location']);
        exit;
    }
    
    // Prepare data for storage (you can add database storage here)
    $data = [
        'full_name' => $fullName,
        'email' => $email,
        'location' => $location,
        'grocery_store' => $groceryStore,
        'restaurant' => $restaurant,
        'platform' => $platform,
        'ip' => $clientIP,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // TODO: Save to database if needed
    // $db = new PDO('mysql:host=localhost;dbname=bukeng', 'user', 'pass');
    // $stmt = $db->prepare("INSERT INTO waitlist ...");
    // $stmt->execute($data);
    
    // For now, log to file
    $logEntry = json_encode($data) . "\n";
    file_put_contents('waitlist_log.txt', $logEntry, FILE_APPEND | LOCK_EX);
    
    // Send email notification
    $emailHTML = formatEmailHTML($data);
    $emailSubject = 'New Waitlist Signup: ' . $fullName;
    
    $emailSent = sendEmail(ADMIN_EMAIL, $emailSubject, $emailHTML, $email);
    
    if (!$emailSent) {
        // Log email failure but still return success to user
        error_log("Failed to send waitlist email for: " . $email);
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Successfully joined the waitlist! You will receive updates soon.'
    ]);
    
} catch (Exception $e) {
    error_log("Waitlist error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
?>