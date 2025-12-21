<?php
$page_title = "Become an Agent";
include_once '../header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="mb-4"><i class="fas fa-user-tie text-primary"></i> Become an Agent</h1>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <p class="lead">Opinionhub.ng is a leading online platform dedicated to conducting polls and surveys across Nigeria. The platform is designed to gather insights from a wide range of demographics, providing valuable data for businesses, governments, and organizations looking to understand the needs and preferences of the Nigerian populace.</p>
                    <p>By working with Opinionhub.ng, agents help bridge the gap between the platform and the public, ensuring that data collected is representative, accurate, and actionable.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="mb-3">Why Become an Agent?</h3>
                    
                    <div class="mb-3">
                        <h5><i class="fas fa-money-bill-wave text-success"></i> Earn Income</h5>
                        <p>As an agent, you earn commissions for every survey or poll you facilitate. The more respondents you engage, the more you can earn, making it a lucrative opportunity for those with strong networks and communication skills. <strong>Earn as much as ₦1,000, airtime or data per poll completed.</strong></p>
                    </div>

                    <div class="mb-3">
                        <h5><i class="fas fa-clock text-primary"></i> Flexible Work</h5>
                        <p>Being an agent allows you to work on your own terms. Whether you're a student, a full-time employee, or a stay-at-home parent, you can easily fit this role into your schedule. All you need is a smartphone or computer with internet access.</p>
                    </div>

                    <div class="mb-3">
                        <h5><i class="fas fa-users text-info"></i> Expand Your Network</h5>
                        <p>Working as an agent for Opinionhub.ng gives you the opportunity to interact with diverse groups of people. This can help you build connections that may be beneficial for your personal and professional growth.</p>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="mb-3">Requirements</h3>
                    <ul>
                        <li>Own a Smartphone with at least Android OS 6.0</li>
                        <li>Be at least eighteen (18) years old</li>
                        <li>Have at least a Secondary School Leaving Certificate</li>
                        <li>Have a functional email address and know how to send and receive emails</li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="mb-3">How It Works</h3>
                    
                    <div class="row text-center mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="p-4">
                                <div class="mb-3">
                                    <i class="fas fa-user-plus fa-3x text-primary"></i>
                                </div>
                                <h5>1. Sign Up</h5>
                                <p>Apply to join our community by signing up and creating your profile.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-4">
                                <div class="mb-3">
                                    <i class="fas fa-tasks fa-3x text-success"></i>
                                </div>
                                <h5>2. Take Task</h5>
                                <p>Give feedback on the products and services that you love and use today.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-4">
                                <div class="mb-3">
                                    <i class="fas fa-money-bill-wave fa-3x text-warning"></i>
                                </div>
                                <h5>3. Get Paid</h5>
                                <p>Earn money for every task completed. Credited within 5 working days.</p>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> After registering and completing your profile, we will review your application. Once approved, you will start receiving tasks via email. Your account will be credited within five (5) working days after completing tasks.
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="mb-3">Getting Started</h3>
                    <ol class="mb-0">
                        <li><strong>Visit the Opinionhub.ng Website:</strong> You're already here! Explore the platform to learn more about our services.</li>
                        <li><strong>Register as an Agent:</strong> Click the button below to create your agent profile with all required details.</li>
                        <li><strong>Start Engaging Respondents:</strong> Once approved, you'll receive access to surveys and polls. Your job is to reach out to potential respondents and encourage participation.</li>
                        <li><strong>Earn Commissions:</strong> Track your earnings and withdraw funds through our transparent system.</li>
                    </ol>
                </div>
            </div>

            <div class="text-center mb-4">
                <?php if(isLoggedIn() && $current_user['role'] != 'agent'): ?>
                    <a href="../agent/register-agent.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus"></i> Create Your Agent Profile
                    </a>
                <?php elseif(!isLoggedIn()): ?>
                    <a href="../signup.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus"></i> Sign Up to Become an Agent
                    </a>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> You are already registered as an agent!
                    </div>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body p-4">
                    <h5 class="mb-3"><i class="fas fa-question-circle text-primary"></i> Have Questions?</h5>
                    <p class="mb-0">Contact us at <a href="mailto:hello@opinionhub.ng">hello@opinionhub.ng</a> or call <a href="tel:+2348033782777">+234 (0) 803 3782 777</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>
