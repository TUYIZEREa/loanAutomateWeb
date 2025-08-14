</div> <!-- /.container -->

<footer class="footer mt-5 py-5 bg-dark text-white border-top">
    <div class="container text-center">
        <!-- Social Media Links -->
        <div class="mb-3">
            <a href="https://facebook.com/loanautomate" target="_blank" class="mx-2 text-white-50" title="Visit us on Facebook"
                aria-label="Facebook">
                <i class="fab fa-facebook-f fa-lg"></i>
            </a>
            <a href="https://twitter.com/loanautomate" target="_blank" class="mx-2 text-white-50" title="Visit us on Twitter"
                aria-label="Twitter">
                <i class="fab fa-twitter fa-lg"></i>
            </a>
            <a href="https://linkedin.com/company/loan-automate" target="_blank" class="mx-2 text-white-50" title="Visit us on LinkedIn"
                aria-label="LinkedIn">
                <i class="fab fa-linkedin-in fa-lg"></i>
            </a>
        </div>

        <!-- Additional Links -->
        <div class="mb-3">
            <a href="<?php echo url('privacy_policy.php'); ?>" class="text-white-50 mx-2 small">Privacy Policy</a>
            |
            <a href="<?php echo url('terms_of_service.php'); ?>" class="text-white-50 mx-2 small">Terms of Service</a>
            |
            <a href="<?php echo url('contact_us.php'); ?>" class="text-white-50 mx-2 small">Contact Us</a>
        </div>

        <!-- Copyright -->
        <p class="mb-0 text-white-50 small">
            &copy; <?php echo date('Y'); ?> <strong>Loan Automate</strong>. All rights reserved.
        </p>
    </div>

    <!-- Scroll-to-Top Button -->
    <button id="scrollTopBtn" class="btn btn-primary rounded-circle shadow"
        style="position: fixed; bottom: 20px; right: 20px; display: none;" title="Back to top"
        aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>
</footer>

<!-- Scripts -->
<!-- Font Awesome Icons -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom Script -->
<script src="/loan-automate/assets/js/main.js"></script>

<script>
    // Show or hide scroll-to-top button
    $(window).on('scroll', function () {
        $('#scrollTopBtn').toggle($(this).scrollTop() > 200);
    });

    // Scroll to top when button clicked
    $('#scrollTopBtn').on('click', function () {
        $('html, body').animate({ scrollTop: 0 }, 'slow');
    });
</script>

</body>

</html>