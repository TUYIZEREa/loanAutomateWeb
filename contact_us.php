<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/utilities.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/templates/header.php';

$success = '';
$error = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $db = Database::getInstance();
            
            // Store contact message in database
            $query = "INSERT INTO contact_messages (name, email, phone, subject, message, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
            
            if ($stmt->execute()) {
                $success = 'Thank you for your message! We will get back to you within 24 hours.';
                
                // Clear form data
                $name = $email = $phone = $subject = $message = '';
            } else {
                $error = 'Sorry, there was an error sending your message. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Sorry, there was an error sending your message. Please try again.';
            error_log("Contact form error: " . $e->getMessage());
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h1 class="h3 mb-0">Contact Us</h1>
                    <p class="text-muted mb-0">We're here to help! Get in touch with us.</p>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Contact Information -->
                        <div class="col-md-4 mb-4">
                            <h4>Get in Touch</h4>
                            <div class="mb-3">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                <strong>Address:</strong><br>
                                <small class="text-muted">
                                    Kigali, Rwanda<br>
                                    Central Business District
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <i class="fas fa-phone text-primary me-2"></i>
                                <strong>Phone:</strong><br>
                                <small class="text-muted">
                                    +250 788 123 456<br>
                                    +250 789 123 456
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <i class="fas fa-envelope text-primary me-2"></i>
                                <strong>Email:</strong><br>
                                <small class="text-muted">
                                    info@loanautomate.com<br>
                                    support@loanautomate.com
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <i class="fas fa-clock text-primary me-2"></i>
                                <strong>Business Hours:</strong><br>
                                <small class="text-muted">
                                    Monday - Friday: 8:00 AM - 6:00 PM<br>
                                    Saturday: 9:00 AM - 3:00 PM<br>
                                    Sunday: Closed
                                </small>
                            </div>
                        </div>

                        <!-- Contact Form -->
                        <div class="col-md-8">
                            <h4>Send us a Message</h4>
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="subject" class="form-label">Subject *</label>
                                        <select class="form-select" id="subject" name="subject" required>
                                            <option value="">Select a subject</option>
                                            <option value="General Inquiry" <?php echo ($subject ?? '') === 'General Inquiry' ? 'selected' : ''; ?>>General Inquiry</option>
                                            <option value="Account Support" <?php echo ($subject ?? '') === 'Account Support' ? 'selected' : ''; ?>>Account Support</option>
                                            <option value="Loan Application" <?php echo ($subject ?? '') === 'Loan Application' ? 'selected' : ''; ?>>Loan Application</option>
                                            <option value="Technical Support" <?php echo ($subject ?? '') === 'Technical Support' ? 'selected' : ''; ?>>Technical Support</option>
                                            <option value="Complaint" <?php echo ($subject ?? '') === 'Complaint' ? 'selected' : ''; ?>>Complaint</option>
                                            <option value="Feedback" <?php echo ($subject ?? '') === 'Feedback' ? 'selected' : ''; ?>>Feedback</option>
                                            <option value="Other" <?php echo ($subject ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message *</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" 
                                              placeholder="Please describe your inquiry or concern..." required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Send Message
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- FAQ Section -->
                    <div class="mt-4">
                        <h4>Frequently Asked Questions</h4>
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq1">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                        How do I apply for a loan?
                                    </button>
                                </h2>
                                <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        You can apply for a loan by logging into your account and navigating to the "Apply for Loan" section. Fill out the required information including the loan amount and duration. Your application will be reviewed by our admin team.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                        How long does withdrawal approval take?
                                    </button>
                                </h2>
                                <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Withdrawal requests are typically processed within 1 minute. If not approved within this time, the request will automatically expire and you'll need to submit a new request.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                        What are the withdrawal charges?
                                    </button>
                                </h2>
                                <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Withdrawal charges vary based on the amount. Charges range from FRW 50 for amounts up to FRW 1,000, up to FRW 1,000 for amounts over FRW 50,000. The exact charges will be shown before you confirm your withdrawal.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4">
                                        How can I reset my password?
                                    </button>
                                </h2>
                                <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        If you've forgotten your password, please contact our support team. We'll help you reset your password securely after verifying your identity.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="<?php echo url('index.php'); ?>" class="btn btn-secondary">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
