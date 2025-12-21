<?php
$page_title = "Frequently Asked Questions (FAQ)";
include_once 'header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="mb-4"><i class="fas fa-question-circle text-primary"></i> Frequently Asked Questions (FAQ)</h1>
            <p class="lead mb-4">Here you can find answers to basic questions about the site and troubleshoot some common issues. If there is anything we missed, or for any other questions, please do contact us using any of the means below.</p>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5><i class="fas fa-envelope text-primary"></i> Contact Opinion Hub NG:</h5>
                    <p class="mb-1"><strong>Email:</strong> <a href="mailto:hello@opinionhub.ng">hello@opinionhub.ng</a></p>
                    <p class="mb-0"><strong>Call Us:</strong> <a href="tel:+2348033782777">+234 (0) 803 3782 777</a> | <a href="tel:+2342012952413">+234-01-2952-413</a></p>
                </div>
            </div>

            <!-- About Opinion Hub NG -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-info-circle"></i> About Opinion Hub NG</h4>
                </div>
                <div class="card-body">
                    <div class="accordion" id="aboutAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#about1">
                                    What is Opinion Hub NG?
                                </button>
                            </h2>
                            <div id="about1" class="accordion-collapse collapse show" data-bs-parent="#aboutAccordion">
                                <div class="accordion-body">
                                    Opinion Hub NG is a leading online platform dedicated to conducting polls and surveys across Nigeria. We gather insights from diverse demographics, providing valuable data for businesses, governments, and organizations.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#about2">
                                    Who can use Opinion Hub NG?
                                </button>
                            </h2>
                            <div id="about2" class="accordion-collapse collapse" data-bs-parent="#aboutAccordion">
                                <div class="accordion-body">
                                    Our platform serves politicians, government agencies, brands, media agencies, pollsters, academics, researchers, consultancies, and anyone interested in understanding public opinion in Nigeria.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agents -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-user-tie"></i> Agents</h4>
                </div>
                <div class="card-body">
                    <div class="accordion" id="agentsAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#agent1">
                                    How do I become an agent?
                                </button>
                            </h2>
                            <div id="agent1" class="accordion-collapse collapse show" data-bs-parent="#agentsAccordion">
                                <div class="accordion-body">
                                    Visit our <a href="agent/become-agent.php">Become an Agent</a> page, complete the registration form with all required details, and submit your application. Once approved, you'll start receiving tasks via email.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#agent2">
                                    How much can I earn as an agent?
                                </button>
                            </h2>
                            <div id="agent2" class="accordion-collapse collapse" data-bs-parent="#agentsAccordion">
                                <div class="accordion-body">
                                    As an agent, you can earn as much as ₦1,000, airtime, or data per poll completed. Your account will be credited within five (5) working days after completing tasks.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#agent3">
                                    What are the requirements to become an agent?
                                </button>
                            </h2>
                            <div id="agent3" class="accordion-collapse collapse" data-bs-parent="#agentsAccordion">
                                <div class="accordion-body">
                                    You must: (1) Own a smartphone with at least Android OS 6.0, (2) Be at least 18 years old, (3) Have at least a Secondary School Leaving Certificate, and (4) Have a functional email address.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clients -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-briefcase"></i> Clients</h4>
                </div>
                <div class="card-body">
                    <div class="accordion" id="clientsAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#client1">
                                    How do I create a poll?
                                </button>
                            </h2>
                            <div id="client1" class="accordion-collapse collapse show" data-bs-parent="#clientsAccordion">
                                <div class="accordion-body">
                                    After logging in as a client, navigate to "Create Poll" from your dashboard. Follow the 3-step process to set basic details, add questions, and configure settings. You can create polls with 12 different question types.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#client2">
                                    What payment methods do you accept?
                                </button>
                            </h2>
                            <div id="client2" class="accordion-collapse collapse" data-bs-parent="#clientsAccordion">
                                <div class="accordion-body">
                                    We use vPay Africa as our secure payment gateway, which accepts credit cards, debit cards, bank transfers, USSD, and other Nigerian payment methods. All transactions are protected and secure.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#client3">
                                    Can I export poll results?
                                </button>
                            </h2>
                            <div id="client3" class="accordion-collapse collapse" data-bs-parent="#clientsAccordion">
                                <div class="accordion-body">
                                    Yes! Basic, Classic, and Enterprise plan users can export poll data and screenshots. Free plan users do not have export access.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Registration -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-user-plus"></i> Account Registration</h4>
                </div>
                <div class="card-body">
                    <div class="accordion" id="accountAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#account1">
                                    How do I create an account?
                                </button>
                            </h2>
                            <div id="account1" class="accordion-collapse collapse show" data-bs-parent="#accountAccordion">
                                <div class="accordion-body">
                                    Click the "Sign Up" button in the navigation menu, choose your account type (Client or Agent), fill in your details, and submit. You'll receive a confirmation email to activate your account.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#account2">
                                    I forgot my password. What should I do?
                                </button>
                            </h2>
                            <div id="account2" class="accordion-collapse collapse" data-bs-parent="#accountAccordion">
                                <div class="accordion-body">
                                    Click "Forgot Password" on the login page, enter your email address, and follow the instructions sent to your email to reset your password.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- General Queries -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0"><i class="fas fa-question"></i> General Queries</h4>
                </div>
                <div class="card-body">
                    <div class="accordion" id="generalAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#general1">
                                    Is my data secure?
                                </button>
                            </h2>
                            <div id="general1" class="accordion-collapse collapse show" data-bs-parent="#generalAccordion">
                                <div class="accordion-body">
                                    Yes! We implement appropriate technical and organizational measures to protect your data. Please read our <a href="privacy-policy.php">Privacy Policy</a> for details on how we handle your information.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#general2">
                                    Can I participate in polls without an account?
                                </button>
                            </h2>
                            <div id="general2" class="accordion-collapse collapse" data-bs-parent="#generalAccordion">
                                <div class="accordion-body">
                                    Yes, you can participate in public polls without creating an account. However, to create polls or become an agent, you must register.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Opinion Hub NG Policies -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="fas fa-file-alt"></i> Opinion Hub NG Policies</h4>
                </div>
                <div class="card-body">
                    <div class="accordion" id="policyAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#policy1">
                                    Where can I find your Terms of Use?
                                </button>
                            </h2>
                            <div id="policy1" class="accordion-collapse collapse show" data-bs-parent="#policyAccordion">
                                <div class="accordion-body">
                                    Our complete Terms of Use are available at <a href="terms.php">this link</a>. By using Opinion Hub NG, you agree to these terms.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#policy2">
                                    What is your refund policy?
                                </button>
                            </h2>
                            <div id="policy2" class="accordion-collapse collapse" data-bs-parent="#policyAccordion">
                                <div class="accordion-body">
                                    Poll credits and subscription payments are generally non-refundable. However, if you experience technical issues that prevent you from using our services, please contact us to discuss your specific situation.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Offers and Discounts -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header" style="background-color: #ff6b6b; color: white;">
                    <h4 class="mb-0"><i class="fas fa-gift"></i> Offers and Discounts</h4>
                </div>
                <div class="card-body">
                    <div class="accordion" id="offersAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#offer1">
                                    Do you offer discounts for bulk purchases?
                                </button>
                            </h2>
                            <div id="offer1" class="accordion-collapse collapse show" data-bs-parent="#offersAccordion">
                                <div class="accordion-body">
                                    Yes! Annual subscriptions offer significant savings compared to monthly payments. Additionally, higher-tier plans include more SMS, email, and WhatsApp units at lower per-unit costs.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#offer2">
                                    Are there special rates for NGOs or educational institutions?
                                </button>
                            </h2>
                            <div id="offer2" class="accordion-collapse collapse" data-bs-parent="#offersAccordion">
                                <div class="accordion-body">
                                    We offer customized pricing for NGOs, educational institutions, and government agencies. Please contact us at <a href="mailto:hello@opinionhub.ng">hello@opinionhub.ng</a> to discuss your needs.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-primary">
                <h5><i class="fas fa-headset"></i> Still Have Questions?</h5>
                <p class="mb-0">If you couldn't find the answer you're looking for, please don't hesitate to reach out to our support team:</p>
                <p class="mb-0 mt-2">
                    <i class="fas fa-envelope"></i> Email: <a href="mailto:hello@opinionhub.ng" class="text-white"><strong>hello@opinionhub.ng</strong></a> | 
                    <i class="fas fa-phone"></i> Call: <a href="tel:+2348033782777" class="text-white"><strong>+234 (0) 803 3782 777</strong></a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
