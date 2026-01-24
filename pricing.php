<?php
$page_title = "Pricing & Plans";
include_once 'header.php';

?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-center mb-4"><i class="fas fa-tags text-primary"></i> Pricing & Plans</h1>
            <p class="text-center lead mb-5">At Opinion Hub NG, we understand that different organizations have varying needs and budgets. That's why we've developed a range of pricing plans designed to suit everyone, from small start-ups to large enterprises and political entities.</p>

            <!-- Pricing Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr class="table-light">
                            <th class="align-middle">Features, Listing & Exposure</th>
                            <th class="text-center align-middle bg-light">
                                <h5 class="mb-0">Free Plan</h5>
                            </th>
                            <th class="text-center align-middle bg-info text-white">
                                <h5 class="mb-0">Basic Plan</h5>
                            </th>
                            <th class="text-center align-middle bg-primary text-white">
                                <h5 class="mb-0">Classic</h5>
                            </th>
                            <th class="text-center align-middle bg-success text-white">
                                <h5 class="mb-0">Enterprise</h5>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Pricing -->
                        <tr>
                            <td><strong>Annual Price</strong></td>
                            <td class="text-center"><h5 class="text-success mb-0">Free</h5></td>
                            <td class="text-center"><h5 class="mb-0">₦<?php echo number_format(getSetting('subscription_price_basic_annual', '392000')); ?></h5></td>
                            <td class="text-center"><h5 class="mb-0">₦<?php echo number_format(getSetting('subscription_price_classic_annual', '735000')); ?></h5></td>
                            <td class="text-center"><h5 class="mb-0">₦<?php echo number_format(getSetting('subscription_price_enterprise_annual', '1050000')); ?></h5></td>
                        </tr>
                        <tr>
                            <td><strong>Monthly</strong></td>
                            <td class="text-center"><h6 class="text-success mb-0">Free</h6></td>
                            <td class="text-center"><h6 class="mb-0">₦<?php echo number_format(getSetting('subscription_price_basic_monthly', '35000')); ?></h6></td>
                            <td class="text-center"><h6 class="mb-0">₦<?php echo number_format(getSetting('subscription_price_classic_monthly', '65000')); ?></h6></td>
                            <td class="text-center"><h6 class="mb-0">₦<?php echo number_format(getSetting('subscription_price_enterprise_monthly', '100000')); ?></h6></td>
                        </tr>

                        <!-- Target Audience -->
                        <tr class="table-light">
                            <td colspan="5"><strong>Target Audience</strong></td>
                        </tr>
                        <tr>
                            <td><em>For those looking to get started with Poll Nigeria</em></td>
                            <td class="text-center"><em>Small businesses, NGOs, and individual researchers</em></td>
                            <td class="text-center"><em>Medium-sized businesses, political campaign teams, and educational institutions</em></td>
                            <td class="text-center"><em>Large enterprises, political parties, and market research firms</em></td>
                            <td class="text-center"></td>
                        </tr>

                        <!-- Core Features -->
                        <tr class="table-light">
                            <td colspan="5"><strong>Core Features</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Number of Monthly Polls</strong></td>
                            <td class="text-center">1</td>
                            <td class="text-center">50</td>
                            <td class="text-center">200</td>
                            <td class="text-center"><span class="badge bg-success">Unlimited</span></td>
                        </tr>
                        <tr>
                            <td><strong>Responses per Poll</strong></td>
                            <td class="text-center">50</td>
                            <td class="text-center">5,000</td>
                            <td class="text-center">20,000</td>
                            <td class="text-center"><span class="badge bg-success">Unlimited</span></td>
                        </tr>
                        <tr>
                            <td><strong>Export Data & Screenshots</strong></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i> Yes</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i> Yes</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i> Yes</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i> Yes</td>
                        </tr>
                        <tr>
                            <td><strong>Social Media Share</strong></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i> Yes</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i> Yes</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i> Yes</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i> Yes</td>
                        </tr>

                        <!-- Messaging Cost -->
                        <tr class="table-light">
                            <td colspan="5"><strong>Messaging Cost</strong></td>
                        </tr>
                        <tr>
                            <td><strong>SMS Invite Credits | Annual Plan</strong></td>
                            <td class="text-center">N/A</td>
                            <td class="text-center">5,000 units</td>
                            <td class="text-center">10,000 units</td>
                            <td class="text-center">15,000 units</td>
                        </tr>
                        <tr>
                            <td><strong>SMS Invite Credits | Monthly Plan</strong></td>
                            <td class="text-center">-</td>
                            <td class="text-center">500</td>
                            <td class="text-center">1,000</td>
                            <td class="text-center">1,500</td>
                        </tr>
                        <tr>
                            <td><strong>SMS Cost per unit per Plan</strong></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('sms_price_free', '20'), 0); ?></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('sms_price_basic', '18'), 0); ?></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('sms_price_classic', '17'), 0); ?></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('sms_price_enterprise', '16'), 0); ?></td>
                        </tr>
                        <tr>
                            <td><strong>E-Mail Invites Credits | Annual Plan</strong></td>
                            <td class="text-center">N/A</td>
                            <td class="text-center">5,000 Units</td>
                            <td class="text-center">10,000</td>
                            <td class="text-center">15,000</td>
                        </tr>
                        <tr>
                            <td><strong>E-Mail Invites Credits | Monthly Plan</strong></td>
                            <td class="text-center">-</td>
                            <td class="text-center">500</td>
                            <td class="text-center">1,000</td>
                            <td class="text-center">1,500</td>
                        </tr>
                        <tr>
                            <td><strong>E-Mail Cost per unit per plan</strong></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('email_price_free', '10'), 0); ?></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('email_price_basic', '8'), 0); ?></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('email_price_classic', '9'), 0); ?></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('email_price_enterprise', '8'), 0); ?></td>
                        </tr>
                        <tr>
                            <td><strong>WhatsApp Invite Credits | Annual Plan</strong></td>
                            <td class="text-center">N/A</td>
                            <td class="text-center">1,000 Units</td>
                            <td class="text-center">5,000 Units</td>
                            <td class="text-center">10,000 Units</td>
                        </tr>
                        <tr>
                            <td><strong>WhatsApp Invite Credits | Monthly Plan</strong></td>
                            <td class="text-center">N/A</td>
                            <td class="text-center">100 Units</td>
                            <td class="text-center">500 Units</td>
                            <td class="text-center">1,000 Units</td>
                        </tr>
                        <tr>
                            <td><strong>WhatsApp per Unit</strong></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('whatsapp_price_free', '24'), 0); ?></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('whatsapp_price_basic', '22'), 0); ?></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('whatsapp_price_classic', '21'), 0); ?></td>
                            <td class="text-center">₦<?php echo number_format(getSetting('whatsapp_price_enterprise', '20'), 0); ?></td>
                        </tr>

                        <!-- Action Buttons -->
                        <tr>
                            <td></td>
                            <td class="text-center py-3">
                                <a href="signup.php" class="btn btn-outline-primary">SIGN-UP</a>
                            </td>
                            <td class="text-center py-3">
                                <a href="client/buy-credits.php" class="btn btn-info text-white">PAY NOW</a>
                            </td>
                            <td class="text-center py-3">
                                <a href="client/buy-credits.php" class="btn btn-primary">PAY NOW</a>
                            </td>
                            <td class="text-center py-3">
                                <a href="client/buy-credits.php" class="btn btn-success">PAY NOW</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle"></i> <strong>Custom Solutions:</strong> Need a tailored plan for your organization? Contact us at <a href="mailto:hello@opinionhub.ng">hello@opinionhub.ng</a> or call <a href="tel:+2348033782777">+234 (0) 803 3782 777</a> to discuss custom pricing.
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
