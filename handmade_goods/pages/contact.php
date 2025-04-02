<?php
session_start();
include __DIR__ . '/../config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = strip_tags(trim($_POST["name"]));
    $email   = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = trim($_POST["message"]);

    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($message)) {
        http_response_code(400);
        $error = "Please fill in the form correctly.";
    } else {
        $api_key = "api-F2E52F5FC44F4B3888E8AE091F4A4282";
        $email_data = [
            "api_key"   => $api_key,
            "sender"    => "handmadegoods@mail2world.com",
            "to"        => [$email],
            "subject"   => "Thank you for contacting us!",
            "html_body" => "<h1>Hello $name,</h1><p>Thank you for reaching out. We have received your message:<br><br>" .
                           nl2br(htmlspecialchars($message)) .
                           "<br><br>We will get back to you shortly.<br><br>Best regards,<br>Handmade Goods</p>",
            "text_body" => "Hello $name,\n\nThank you for reaching out. We have received your message:\n" .
                           $message .
                           "\n\nWe will get back to you shortly.\n\nBest regards,\nHandmade Goods"
        ];

        $ch = curl_init("https://api.smtp2go.com/v3/email/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'accept: application/json',
            'X-Smtp2go-Api: ' . $api_key
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = 'cURL Error: ' . curl_error($ch);
        } else {
            $responseData = json_decode($response, true);
            if (isset($responseData['data']['error']) && !empty($responseData['data']['error'])) {
                $error = "SMTP2GO error: " . $responseData['data']['error'];
            } else {
                // header("Location: home.php");
                exit;
            }
        }
        curl_close($ch);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us</title>
</head>
<body>
    <div class="contact-form-container">
        <form id="contactForm" method="POST" action="contact.php" novalidate>
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
                <small class="error-message" id="nameError"></small>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <small class="error-message" id="emailError"></small>
            </div>

            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                <small class="error-message" id="messageError"></small>
            </div>

            <div class="text-center" style="margin-top: 2rem;">
                <button type="submit" class="white-button">Submit</button>
            </div>

            <div class="status-message" id="formStatus" style="text-align: center; margin-top: 1rem;">
                <?php 
                    if (!empty($error)) {
                        echo '<span class="error-message">' . $error . '</span>';
                    } elseif (!empty($status_message)) {
                        echo $status_message;
                    }
                ?>
            </div>
        </form>
    </div>
</body>
</html>
