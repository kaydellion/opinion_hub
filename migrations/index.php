<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migrations - Opinion Hub NG</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-database me-2"></i>Database Migrations</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Important:</strong> Run these migrations to enable all features. Each migration can be run independently and is safe to run multiple times.
                        </div>

                        <div class="list-group">
                            <!-- Poll Slug Migration -->
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">
                                            <i class="fas fa-link text-primary me-2"></i>Poll Slug Migration
                                        </h5>
                                        <p class="mb-2 text-muted">
                                            Adds slug column to polls table for pretty URLs (e.g., view-poll/my-poll-title)
                                        </p>
                                        <small class="text-muted">
                                            <strong>Fixes:</strong> "Poll not found" errors, empty view-poll URLs
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <a href="run_poll_slug_migration.php" class="btn btn-primary" target="_blank">
                                            <i class="fas fa-play me-1"></i>Run
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Poll Payments & Agent Earnings Migration -->
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">
                                            <i class="fas fa-money-bill-wave text-success me-2"></i>Poll Payments & Agent Earnings System
                                        </h5>
                                        <p class="mb-2 text-muted">
                                            Adds comprehensive poll payment tracking, agent earnings, SMS delivery status, and databank (paid results) features
                                        </p>
                                        <small class="text-muted">
                                            <strong>Features:</strong> Client poll payments, agent commission tracking, SMS delivery tracking, paid poll results access
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <a href="run_poll_payments_migration.php" class="btn btn-success" target="_blank">
                                            <i class="fas fa-play me-1"></i>Run
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- WhatsApp & Email Credits Migration -->
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">
                                            <i class="fas fa-credit-card text-info me-2"></i>WhatsApp & Email Credits
                                        </h5>
                                        <p class="mb-2 text-muted">
                                            Adds WhatsApp and Email credits fields to users table for multi-channel messaging support
                                        </p>
                                        <small class="text-muted">
                                            <strong>Features:</strong> WhatsApp messaging credits, Email messaging credits, Admin bulk credit management
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <a href="run_whatsapp_email_credits.php" class="btn btn-info" target="_blank">
                                            <i class="fas fa-play me-1"></i>Run
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Referral System Migration -->
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">
                                            <i class="fas fa-users text-success me-2"></i>Referral System Migration
                                        </h5>
                                        <p class="mb-2 text-muted">
                                            Adds referral tracking columns and agent earnings table for the referral system
                                        </p>
                                        <small class="text-muted">
                                            <strong>Fixes:</strong> Agent referrals page errors, referral code functionality
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <a href="run_referral_migration.php" class="btn btn-success" target="_blank">
                                            <i class="fas fa-play me-1"></i>Run
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Message Logs Migration -->
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">
                                            <i class="fas fa-envelope text-info me-2"></i>Message Logs Migration
                                        </h5>
                                        <p class="mb-2 text-muted">
                                            Creates message_logs and messaging_credits tables for tracking invites sent
                                        </p>
                                        <small class="text-muted">
                                            <strong>Fixes:</strong> "Call to member function bind_param() on bool" error in send-invites.php
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <a href="run_message_logs_migration.php" class="btn btn-info" target="_blank">
                                            <i class="fas fa-play me-1"></i>Run
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Agent Suspended Status Migration -->
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">
                                            <i class="fas fa-user-slash text-warning me-2"></i>Agent Suspended Status Migration
                                        </h5>
                                        <p class="mb-2 text-muted">
                                            Adds 'suspended' status to agent_status column for admin agent suspension
                                        </p>
                                        <small class="text-muted">
                                            <strong>Enables:</strong> Admin ability to suspend and unsuspend agent accounts
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <a href="run_agent_suspended_migration.php" class="btn btn-warning" target="_blank">
                                            <i class="fas fa-play me-1"></i>Run
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Blog Cancelled Status Migration -->
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">
                                            <i class="fas fa-blog text-secondary me-2"></i>Blog Cancelled Status Migration
                                        </h5>
                                        <p class="mb-2 text-muted">
                                            Adds 'cancelled' status to blog_posts for cancelling/unpublishing live posts
                                        </p>
                                        <small class="text-muted">
                                            <strong>Enables:</strong> Admin ability to cancel/unpublish approved blog posts
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <a href="run_blog_cancelled_migration.php" class="btn btn-secondary" target="_blank">
                                            <i class="fas fa-play me-1"></i>Run
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5><i class="fas fa-question-circle me-2"></i>How to Run Migrations</h5>
                        <ol>
                            <li>Click the <strong>Run</strong> button next to each migration</li>
                            <li>Wait for the "Migration completed successfully" message</li>
                            <li>Close the migration window</li>
                            <li>Proceed to the next migration if needed</li>
                        </ol>

                        <div class="alert alert-success mt-4">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Safe to run:</strong> All migrations check if changes already exist and skip if not needed.
                        </div>

                        <div class="text-center mt-4">
                            <a href="../" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Back to Homepage
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="card mt-4 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-tools me-2"></i>After Running Migrations</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><a href="../polls.php"><i class="fas fa-poll me-1"></i> Browse Polls</a></li>
                                    <li><a href="../agent/referrals.php"><i class="fas fa-users me-1"></i> Agent Referrals</a></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><a href="../agent/share-poll.php"><i class="fas fa-share-alt me-1"></i> Share Polls</a></li>
                                    <li><a href="../search.php"><i class="fas fa-search me-1"></i> Search</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
