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