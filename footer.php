
</main>

<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <h5>Company</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo SITE_URL; ?>about.php">About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>who-uses.php">Who Uses OpinionHub.ng?</a></li>
                    <li><a href="<?php echo SITE_URL; ?>contact.php">Contact Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>privacy-policy.php">Privacy Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>terms.php">Terms of Use</a></li>
                    <li><a href="<?php echo SITE_URL; ?>cookie-policy.php">Cookie Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>faq.php">FAQ</a></li>
                    <li><a href="<?php echo SITE_URL; ?>blog.php">Blog</a></li>
                    <li><a href="<?php echo SITE_URL; ?>contact.php">Help Center</a></li>
                </ul>
            </div>
            
            <div class="col-md-3">
                <h5>Services</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo SITE_URL; ?>agent/become-agent.php">Become an Agent</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pricing.php">Pricing & Plans</a></li>
                    <li><a href="<?php echo SITE_URL; ?>databank.php">Databank</a></li>
                    <li><a href="<?php echo SITE_URL; ?>advertise.php">Advertise With Us</a></li>
                </ul>
            </div>
            
            <div class="col-md-3">
                <h5>Who Uses OpinionHub.ng?</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo SITE_URL; ?>who-uses.php?section=politicians">For Politicians & Government</a></li>
                    <li><a href="<?php echo SITE_URL; ?>who-uses.php?section=brands">For Brands</a></li>
                    <li><a href="<?php echo SITE_URL; ?>who-uses.php?section=media">For Media Agencies</a></li>
                    <li><a href="<?php echo SITE_URL; ?>who-uses.php?section=pollsters">For Pollsters</a></li>
                    <li><a href="<?php echo SITE_URL; ?>who-uses.php?section=academics">For Academics & Researchers</a></li>
                    <li><a href="<?php echo SITE_URL; ?>who-uses.php?section=consultancies">For Consultancies</a></li>
                </ul>
            </div>
            
            <div class="col-md-3">
                <h5>Contact</h5>
                <p>
                    <i class="fas fa-phone"></i> +234 (0) 803 3782 777<br>
                    <i class="fas fa-envelope"></i> <a href="mailto:hello@opinionhub.ng">hello@opinionhub.ng</a>
                </p>
            </div>
        </div>
        
        <hr class="bg-secondary">

        <!-- Disclaimer -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-light border text-muted small" role="alert">
                    <strong>DISCLAIMER:</strong> The content on OpinionHub.ng is provided for informational, opinion-sharing, and polling purposes only. Opinions expressed by users are those of the individual contributors and do not necessarily reflect the views of OpinionHub.ng, its owners, affiliates, or partners.
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 text-center">
                <p>&copy; 2025 Opinion Hub NG. All rights reserved. | Powered by <a href="https://foramfera.com/" target="_blank" rel="noopener noreferrer">Foraminifera Market Research Limited</a></p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<!-- VPay Dropin SDK -->
<script src="<?php echo getVPayScriptUrl(); ?>"></script>

<script>
// VPay Configuration
window.VPAY_CONFIG = {
    key: '<?php echo VPAY_PUBLIC_KEY; ?>',
    domain: '<?php echo getVPayEnvironment(); ?>',
    customerLogo: '<?php echo SITE_URL; ?>uploads/<?php echo SITE_LOGO; ?>',
    customerService: '<?php echo SITE_PHONE; ?>,<?php echo SITE_EMAIL; ?>'
};

// Wait for VPayDropin to load
window.addEventListener('load', function() {
    if (typeof VPayDropin === 'undefined') {
        console.error('VPayDropin SDK failed to load from <?php echo getVPayScriptUrl(); ?>');
    } else {
        console.log('VPayDropin SDK loaded successfully (<?php echo getVPayEnvironment(); ?> mode)');
    }
});

// Track advertisement click
function trackAdClick(adId) {
    if (!adId) return;
    
    fetch('<?php echo SITE_URL; ?>track-ad.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ad_id=' + adId
    }).catch(err => console.error('Failed to track ad click:', err));
}
</script>

</body>
</html>