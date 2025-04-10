<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = htmlspecialchars($_POST['name']);
    $email   = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars($_POST['message']);

    $api_key = "api-DFEA151D81194B3EB9B6CF30891D53A5";
    $subject = "Hello " . $name;
    $email_data = [
        "api_key" => $api_key,
        "sender" => "handmadegoods@mail2world.com",
        "to" => [$email],
        "text_body" => "Thank you for reaching out. We have received your message and we will get back to you shortly.\nBest regards,\nHandmade Goods",
    ];

    $ch = curl_init("https://api.smtp2go.com/v3/email/send");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'accept: application/json']);

    $response = curl_exec($ch);
    curl_close($ch);

    header('Content-Type: application/json');
    if ($response === false) {
        echo json_encode([
            "status"  => "error",
            "message" => "Failed to send message. Please try again later."
        ]);
    } else {
        echo json_encode([
            "status"  => "success",
            "message" => "Thank you for contacting us. Your message has been sent."
        ]);
    }
    exit();
}
?>
<div class="contact-form-container">
    <form id="contactForm" method="POST" action="get_in_touch.php" novalidate>
        <div class="mb-3 w-100">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control w-100" id="name" name="name" required>
            <small class="error-message text-danger" id="nameError"></small>
        </div>

        <div class="mb-3 w-100">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control w-100" id="email" name="email" required>
            <small class="error-message text-danger" id="emailError"></small>
        </div>

        <div class="mb-3 w-100">
            <label for="message" class="form-label">Message</label>
            <textarea class="form-control w-100" id="message" name="message" rows="4" required></textarea>
            <small class="error-message text-danger" id="messageError"></small>
        </div>

        <div class="text-center d-flex align-items-center justify-content-center mt-5 w-100">
            <button type="submit" class="m-btn w-100">Submit</button>
        </div>

        <div class="status-message text-center mt-3 w-100" id="formStatus"></div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('#contactModal').on('shown.bs.modal', function () {
        $('#contactModalBody').load('contact.php', function() {
            $(document).on('input change', '#contactForm input[required], #contactForm textarea[required]', function() {
                checkFormValidity();
            });
            checkFormValidity();
        });
    });

    function checkFormValidity() {
        const form = document.getElementById('contactForm');
        const submitButton = form.querySelector('button[type="submit"]');
        const requiredFields = form.querySelectorAll('input[required], textarea[required]');
        let valid = true;
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                valid = false;
            }
        });
        submitButton.disabled = !valid;
    }

    $('#contactForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('contact.php', formData, function(data) {
            if (data.status === "success") {
                $('#formStatus').html('<div class="alert alert-success">' + data.message + '</div>');
                setTimeout(function() {
                    $('#contactModal').modal('hide');
                }, 2000);
            } else {
                $('#formStatus').html('<div class="alert alert-danger">' + data.message + '</div>');
            }
        }, 'json');
    });
});
</script>
