<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/utilities.php';
require_once __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h1 class="h3 mb-0">Privacy Policy</h1>
                    <p class="text-muted mb-0">Last updated: <?php echo date('F d, Y'); ?></p>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h2>1. Introduction</h2>
                        <p>Loan Automate ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our financial services platform.</p>
                    </div>

                    <div class="mb-4">
                        <h2>2. Information We Collect</h2>
                        <h3>2.1 Personal Information</h3>
                        <ul>
                            <li>Full name and contact details</li>
                            <li>Email address and phone number</li>
                            <li>Account information and transaction history</li>
                            <li>Identity verification documents</li>
                            <li>Financial information for loan applications</li>
                        </ul>

                        <h3>2.2 Technical Information</h3>
                        <ul>
                            <li>IP address and device information</li>
                            <li>Browser type and version</li>
                            <li>Operating system</li>
                            <li>Usage patterns and preferences</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>3. How We Use Your Information</h2>
                        <ul>
                            <li>Process loan applications and withdrawals</li>
                            <li>Verify your identity and prevent fraud</li>
                            <li>Provide customer support and services</li>
                            <li>Send important notifications and updates</li>
                            <li>Comply with legal and regulatory requirements</li>
                            <li>Improve our services and user experience</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>4. Information Sharing</h2>
                        <p>We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
                        <ul>
                            <li>With your explicit consent</li>
                            <li>To comply with legal obligations</li>
                            <li>To protect our rights and prevent fraud</li>
                            <li>With trusted service providers who assist in our operations</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>5. Data Security</h2>
                        <p>We implement industry-standard security measures to protect your information:</p>
                        <ul>
                            <li>Encryption of sensitive data in transit and at rest</li>
                            <li>Secure authentication and access controls</li>
                            <li>Regular security audits and updates</li>
                            <li>Employee training on data protection</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>6. Your Rights</h2>
                        <p>You have the right to:</p>
                        <ul>
                            <li>Access your personal information</li>
                            <li>Correct inaccurate information</li>
                            <li>Request deletion of your data</li>
                            <li>Opt-out of marketing communications</li>
                            <li>File a complaint with relevant authorities</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>7. Data Retention</h2>
                        <p>We retain your information for as long as necessary to provide our services and comply with legal obligations. Financial records are typically retained for 7 years as required by law.</p>
                    </div>

                    <div class="mb-4">
                        <h2>8. Cookies and Tracking</h2>
                        <p>We use cookies and similar technologies to enhance your experience, analyze usage, and provide personalized content. You can control cookie settings through your browser preferences.</p>
                    </div>

                    <div class="mb-4">
                        <h2>9. Children's Privacy</h2>
                        <p>Our services are not intended for individuals under 18 years of age. We do not knowingly collect personal information from children.</p>
                    </div>

                    <div class="mb-4">
                        <h2>10. International Transfers</h2>
                        <p>Your information may be processed in countries other than your own. We ensure appropriate safeguards are in place to protect your data during international transfers.</p>
                    </div>

                    <div class="mb-4">
                        <h2>11. Changes to This Policy</h2>
                        <p>We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the new policy on our website and updating the "Last updated" date.</p>
                    </div>

                    <div class="mb-4">
                        <h2>12. Contact Us</h2>
                        <p>If you have questions about this Privacy Policy or our data practices, please contact us:</p>
                        <ul>
                            <li><strong>Email:</strong> privacy@loanautomate.com</li>
                            <li><strong>Phone:</strong> +250 788 123 456</li>
                            <li><strong>Address:</strong> Kigali, Rwanda</li>
                        </ul>
                    </div>

                    <div class="text-center mt-5">
                        <a href="<?php echo url('index.php'); ?>" class="btn btn-primary">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
