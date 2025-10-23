<?php
/**
 * Email Unsubscribe Handler
 * M-EcommerceCRM - Unsubscribe Management
 */

require_once '../config/database.php';
require_once '../includes/email_manager.php';

$trackingId = $_GET['t'] ?? '';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $trackingId) {
    $reason = $_POST['reason'] ?? '';
    $emailManager = new EmailManager();
    
    if ($emailManager->unsubscribe($trackingId, $reason)) {
        $message = 'You have been successfully unsubscribed from our mailing list.';
    } else {
        $error = 'Unable to process your unsubscribe request. Please contact support.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - M-EcommerceCRM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Unsubscribe</h1>
        
        <?php if ($message): ?>
            <p class="success"><?php echo htmlspecialchars($message); ?></p>
        <?php elseif ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php else: ?>
            <p>We're sorry to see you go. Please let us know why you're unsubscribing:</p>
            
            <form method="POST">
                <p>
                    <label><input type="radio" name="reason" value="too_frequent"> Emails are too frequent</label><br>
                    <label><input type="radio" name="reason" value="not_relevant"> Content is not relevant</label><br>
                    <label><input type="radio" name="reason" value="never_signed_up"> I never signed up for this</label><br>
                    <label><input type="radio" name="reason" value="other"> Other reason</label>
                </p>
                
                <button type="submit" class="btn">Unsubscribe</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
