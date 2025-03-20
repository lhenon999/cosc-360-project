<footer class="footer">
    <div class="footer-container">
        
        <div class="footer-section">
            <h3>About Handmade Goods</h3>
            <p>Discover unique local products crafted with care. Supporting artisans worldwide.</p>
        </div>

        <div class="footer-section">
            <h3>About Us</h3>
                <p>+1 (234)-567-890</p>
            <p>
                3333 University way, Kelowna, BC, Canada <br>
                <strong>Email:</strong> <a href="mailto:info@handmadegoods.com">info@handmadegoods.com</a>
            </p>
            <p><a href="about.php">Learn More</a></p>
        </div>

        <div class="footer-section">
            <h3>Customer Support</h3>
            <ul>
                <li><a href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Contact Us</a></li>
                <li><a href="#">Shipping & Returns</a></li>
                <li><a href="#">FAQs</a></li>
                <li><a href="#">Terms & Conditions</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; <?php echo date('Y'); ?> Handmade Goods. All rights reserved.
    </div>
</footer>

<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalLabel">Contact Us</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="contactModalBody">
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    $('#contactModal').on('show.bs.modal', function () {
        $('#contactModalBody').load('contact.php');
    });
});
</script>
