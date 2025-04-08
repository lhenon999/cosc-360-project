<footer class="footer">
    <div class="footer-container">
        
        <div class="footer-section">
            <a href="/"><h3>Handmade Goods</h3></a>
            <p>Discover unique local products crafted with care. Supporting artisans worldwide.</p>
            <p><a href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Contact Us</a></p>
        </div>

        <div class="footer-section">
        <a href="/about"><h3>About Us</h3></a>
        <a href="tel:+12345678900"><p>+1 (234)-567-8900</p></a>
        <p>
            <strong>Email:</strong> <a href="mailto:info@handmadegoods.com">info@handmadegoods.com</a>
        </p>
        <p>3333 University way, Kelowna, BC, Canada</p>
            
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
