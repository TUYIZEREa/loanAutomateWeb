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
                    <h1 class="h3 mb-0">Terms of Service</h1>
                    <p class="text-muted mb-0">Last updated: <?php echo date('F d, Y'); ?></p>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h2>1. Acceptance of Terms</h2>
                        <p>By accessing and using Loan Automate ("the Service"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
                    </div>

                    <div class="mb-4">
                        <h2>2. Description of Service</h2>
                        <p>Loan Automate is a financial services platform that provides:</p>
                        <ul>
                            <li>Savings account management</li>
                            <li>Loan application and management</li>
                            <li>Money transfer services</li>
                            <li>Withdrawal services</li>
                            <li>Financial transaction tracking</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>3. User Accounts</h2>
                        <h3>3.1 Account Creation</h3>
                        <ul>
                            <li>You must be at least 18 years old to create an account</li>
                            <li>You must provide accurate and complete information</li>
                            <li>You are responsible for maintaining the security of your account</li>
                            <li>You must notify us immediately of any unauthorized use</li>
                        </ul>

                        <h3>3.2 Account Responsibilities</h3>
                        <ul>
                            <li>Keep your login credentials secure</li>
                            <li>Update your information when it changes</li>
                            <li>Not share your account with others</li>
                            <li>Comply with all applicable laws and regulations</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>4. Financial Services Terms</h2>
                        <h3>4.1 Savings Accounts</h3>
                        <ul>
                            <li>Minimum balance requirements may apply</li>
                            <li>Interest rates are subject to change</li>
                            <li>Withdrawal limits and fees may apply</li>
                            <li>Account statements will be provided regularly</li>
                        </ul>

                        <h3>4.2 Loan Services</h3>
                        <ul>
                            <li>Loan approval is subject to credit assessment</li>
                            <li>Interest rates and fees will be clearly disclosed</li>
                            <li>Repayment schedules must be adhered to</li>
                            <li>Late payments may result in additional charges</li>
                        </ul>

                        <h3>4.3 Withdrawal Services</h3>
                        <ul>
                            <li>Withdrawal requests require admin approval</li>
                            <li>Processing times may vary</li>
                            <li>Charges may apply based on amount and frequency</li>
                            <li>Requests expire after 1 minute if not approved</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>5. Prohibited Activities</h2>
                        <p>You agree not to:</p>
                        <ul>
                            <li>Use the service for illegal purposes</li>
                            <li>Attempt to gain unauthorized access to the system</li>
                            <li>Interfere with the service's operation</li>
                            <li>Provide false or misleading information</li>
                            <li>Engage in money laundering or fraud</li>
                            <li>Violate any applicable laws or regulations</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>6. Fees and Charges</h2>
                        <ul>
                            <li>Service fees may apply to certain transactions</li>
                            <li>Withdrawal charges are based on amount ranges</li>
                            <li>Late payment fees may apply to loans</li>
                            <li>All fees will be clearly disclosed before processing</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>7. Privacy and Data Protection</h2>
                        <p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of the Service, to understand our practices.</p>
                    </div>

                    <div class="mb-4">
                        <h2>8. Security</h2>
                        <ul>
                            <li>We implement security measures to protect your data</li>
                            <li>You are responsible for maintaining account security</li>
                            <li>Report any security concerns immediately</li>
                            <li>We may suspend accounts for security reasons</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>9. Limitation of Liability</h2>
                        <p>Loan Automate shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses.</p>
                    </div>

                    <div class="mb-4">
                        <h2>10. Dispute Resolution</h2>
                        <p>Any disputes arising from the use of our services will be resolved through:</p>
                        <ol>
                            <li>Direct communication with our customer service</li>
                            <li>Mediation if necessary</li>
                            <li>Legal proceedings in Rwandan courts if required</li>
                        </ol>
                    </div>

                    <div class="mb-4">
                        <h2>11. Termination</h2>
                        <p>We may terminate or suspend your account immediately, without prior notice, for conduct that we believe violates these Terms of Service or is harmful to other users, us, or third parties.</p>
                    </div>

                    <div class="mb-4">
                        <h2>12. Changes to Terms</h2>
                        <p>We reserve the right to modify these terms at any time. We will notify users of any material changes by posting the new terms on our website.</p>
                    </div>

                    <div class="mb-4">
                        <h2>13. Governing Law</h2>
                        <p>These Terms of Service shall be governed by and construed in accordance with the laws of Rwanda.</p>
                    </div>

                    <div class="mb-4">
                        <h2>14. Contact Information</h2>
                        <p>For questions about these Terms of Service, please contact us:</p>
                        <ul>
                            <li><strong>Email:</strong> legal@loanautomate.com</li>
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
