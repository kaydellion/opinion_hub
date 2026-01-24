<?php
$page_title = "Who Uses OpinionHub.ng?";
include_once 'header.php';

// Get the section from URL parameter
$section = isset($_GET['section']) ? sanitize($_GET['section']) : 'overview';

// Define content for each section
$sections = [
    'overview' => [
        'title' => 'Who Uses OpinionHub.ng?',
        'content' => '<p>Public opinion is at the heart of every decision-making process—whether in government, business, media, academia, or consulting. Understanding what people think, how they behave, and what influences their choices helps organizations make better strategies, craft effective policies, and deliver impactful results.</p>

        <p>At OpinionHub.ng, we provide a modern, data-driven platform that allows organizations across sectors to collect insights, run surveys, conduct polls, and analyze opinions in real time. Below, we explore how different industries can benefit from leveraging our services.</p>

        <div class="row mt-4">
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">For Politicians & Government</h5>
                        <p class="card-text">Measure public sentiment, test policies, and strengthen democracy with real-time polling data.</p>
                        <a href="?section=politicians" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">For Brands</h5>
                        <p class="card-text">Understand consumer behavior, track brand health, and make data-driven marketing decisions.</p>
                        <a href="?section=brands" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">For Media Agencies</h5>
                        <p class="card-text">Create relevant content, segment audiences, and measure campaign effectiveness.</p>
                        <a href="?section=media" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">For Pollsters</h5>
                        <p class="card-text">Scale your polling operations with advanced tools, analytics, and transparent methodologies.</p>
                        <a href="?section=pollsters" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">For Academics & Researchers</h5>
                        <p class="card-text">Access reliable datasets for studies on social behavior, economics, culture, and governance.</p>
                        <a href="?section=academics" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">For Consultancies</h5>
                        <p class="card-text">Back up your recommendations with hard data and evidence-based insights.</p>
                        <a href="?section=consultancies" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
            </div>
        </div>'
    ],

    'politicians' => [
        'title' => 'For Politicians & Government',
        'content' => '<p>In today\'s democracy, governance thrives on feedback. Politicians and government agencies that fail to listen to the people risk making decisions that are disconnected from reality. OpinionHub.ng bridges this gap by offering a reliable way to measure public sentiment.</p>

        <h4>Key Benefits:</h4>
        <ul>
            <li><strong>Policy Testing:</strong> Before rolling out a policy, governments can use surveys to gauge how citizens perceive it. This reduces the risk of backlash and helps refine implementation strategies.</li>
            <li><strong>Approval Ratings:</strong> Politicians can monitor their approval ratings regularly, identifying areas where performance perception is low and addressing them before elections.</li>
            <li><strong>Election Campaigns:</strong> Polls help political candidates understand voter priorities, segment electorates, and craft resonant campaign messages.</li>
            <li><strong>Citizen Engagement:</strong> Beyond elections, consistent feedback channels allow governments to build trust, transparency, and accountability.</li>
        </ul>

        <p>By using data-driven insights, political leaders can make informed decisions, strengthen democracy, and maintain credibility with the public.</p>

        <div class="alert alert-info">
            <strong>Ready to engage with your constituents?</strong>
            <a href="' . SITE_URL . 'register.php" class="btn btn-primary btn-sm ms-2">Create Account</a>
            <a href="' . SITE_URL . 'login.php" class="btn btn-outline-primary btn-sm ms-1">Sign In</a>
        </div>'
    ],

    'brands' => [
        'title' => 'For Brands',
        'content' => '<p>For businesses and brands, consumer perception is everything. In a competitive market, success lies in knowing what customers think about your product, service, or overall brand image. OpinionHub.ng provides the tools for brands to stay ahead.</p>

        <h4>Key Benefits:</h4>
        <ul>
            <li><strong>Market Research:</strong> Businesses can test new product ideas, measure demand, and explore pricing strategies through polls and targeted surveys.</li>
            <li><strong>Customer Feedback:</strong> Post-purchase surveys can reveal satisfaction levels, areas of improvement, and consumer expectations.</li>
            <li><strong>Brand Health Tracking:</strong> OpinionHub.ng enables brands to track reputation, awareness, and loyalty over time.</li>
            <li><strong>Advertising Effectiveness:</strong> Companies can test the effectiveness of marketing campaigns by gauging how audiences perceive ads, slogans, or product positioning.</li>
        </ul>

        <p>With these insights, brands don\'t just react to trends—they create them, anticipate market shifts, and sustain customer loyalty.</p>

        <div class="alert alert-info">
            <strong>Ready to understand your customers better?</strong>
            <a href="' . SITE_URL . 'register.php" class="btn btn-primary btn-sm ms-2">Create Account</a>
            <a href="' . SITE_URL . 'login.php" class="btn btn-outline-primary btn-sm ms-1">Sign In</a>
        </div>'
    ],

    'media' => [
        'title' => 'For Media Agencies',
        'content' => '<p>Media is the heartbeat of public discourse, and agencies must constantly stay in touch with what audiences care about. OpinionHub.ng empowers media agencies with data that fuels content creation, advertising strategies, and audience engagement.</p>

        <h4>Key Benefits:</h4>
        <ul>
            <li><strong>Content Relevance:</strong> By analyzing trending topics and audience interests, media agencies can design content that resonates with readers, listeners, or viewers.</li>
            <li><strong>Audience Segmentation:</strong> Surveys allow agencies to identify different audience demographics and tailor communication accordingly.</li>
            <li><strong>Campaign Measurement:</strong> OpinionHub.ng provides the tools to test how well media campaigns perform and how they influence public opinion.</li>
            <li><strong>Story Validation:</strong> Before running with a headline or investigative piece, media organizations can use polling to confirm if the issue resonates with their audience.</li>
        </ul>

        <p>In an era of fast-moving information, reliable audience insights give media houses a competitive edge in shaping narratives and maintaining trust.</p>

        <div class="alert alert-info">
            <strong>Ready to engage your audience better?</strong>
            <a href="' . SITE_URL . 'register.php" class="btn btn-primary btn-sm ms-2">Create Account</a>
            <a href="' . SITE_URL . 'login.php" class="btn btn-outline-primary btn-sm ms-1">Sign In</a>
        </div>'
    ],

    'pollsters' => [
        'title' => 'For Pollsters',
        'content' => '<p>Pollsters live and breathe data. For them, OpinionHub.ng offers a digital hub where polling becomes faster, smarter, and more transparent.</p>

        <h4>Key Benefits:</h4>
        <ul>
            <li><strong>Efficient Tools:</strong> Our platform provides ready-made templates, analytics dashboards, and demographic targeting to ease the polling process.</li>
            <li><strong>Scalability:</strong> Pollsters can conduct surveys across diverse population groups and regions in Nigeria, reaching a wider audience.</li>
            <li><strong>Accuracy & Transparency:</strong> OpinionHub.ng leverages technology to minimize bias, track responses, and present real-time results.</li>
            <li><strong>Collaboration Opportunities:</strong> Independent pollsters can partner with organizations, media houses, and government institutions through the platform to increase visibility.</li>
        </ul>

        <p>Ultimately, we give pollsters the infrastructure to deliver credible insights that influence national conversations.</p>

        <div class="alert alert-info">
            <strong>Ready to scale your polling operations?</strong>
            <a href="' . SITE_URL . 'agent/become-agent.php" class="btn btn-primary btn-sm ms-2">Become an Agent</a>
            <a href="' . SITE_URL . 'login.php" class="btn btn-outline-primary btn-sm ms-1">Sign In</a>
        </div>'
    ],

    'academics' => [
        'title' => 'For Academics & Researchers',
        'content' => '<p>Academic institutions and independent researchers often need structured data for studies on social behavior, economics, culture, and governance. OpinionHub.ng offers them a rich platform for survey design, data collection, and analysis.</p>

        <h4>Key Benefits:</h4>
        <ul>
            <li><strong>Social Research:</strong> Academics can investigate societal issues such as youth unemployment, healthcare access, or public trust in institutions.</li>
            <li><strong>Behavioral Studies:</strong> Surveys on consumer patterns, cultural attitudes, and technology adoption provide valuable academic insights.</li>
            <li><strong>Data for Publications:</strong> Reliable datasets can enrich journal articles, dissertations, and policy papers.</li>
            <li><strong>Educational Use:</strong> OpinionHub.ng serves as a live teaching tool for students in fields like political science, sociology, marketing, and public administration.</li>
        </ul>

        <p>For the academic community, our platform opens up affordable, accessible, and reliable avenues for evidence-based research.</p>

        <div class="alert alert-info">
            <strong>Ready to enhance your research?</strong>
            <a href="' . SITE_URL . 'register.php" class="btn btn-primary btn-sm ms-2">Create Account</a>
            <a href="' . SITE_URL . 'login.php" class="btn btn-outline-primary btn-sm ms-1">Sign In</a>
        </div>'
    ],

    'consultancies' => [
        'title' => 'For Consultancies',
        'content' => '<p>Consultancies thrive on data-driven recommendations. Whether advising governments, NGOs, or corporations, consultants need accurate information to validate strategies. OpinionHub.ng becomes their research partner, providing critical insights to back up advisory work.</p>

        <h4>Key Benefits:</h4>
        <ul>
            <li><strong>Market Entry Studies:</strong> Consultants can help businesses expand by running opinion surveys on consumer readiness and competitive landscapes.</li>
            <li><strong>Policy Advisory:</strong> Development and strategy consultants can assess the impact of interventions by gathering public feedback before and after project execution.</li>
            <li><strong>Reputation Audits:</strong> By leveraging polls, consultants can measure brand or political reputation and suggest improvement strategies.</li>
            <li><strong>Evidence-Based Proposals:</strong> Accurate survey data strengthens proposals and pitches, giving consultants credibility and competitive advantage.</li>
        </ul>

        <p>With OpinionHub.ng, consultants move beyond assumptions and deliver recommendations backed by hard data.</p>

        <div class="alert alert-info">
            <strong>Ready to strengthen your proposals?</strong>
            <a href="' . SITE_URL . 'register.php" class="btn btn-primary btn-sm ms-2">Create Account</a>
            <a href="' . SITE_URL . 'login.php" class="btn btn-outline-primary btn-sm ms-1">Sign In</a>
        </div>'
    ]
];

// Set page title based on section
if (isset($sections[$section])) {
    $current_section = $sections[$section];
    $page_title = $current_section['title'];
} else {
    $current_section = $sections['overview'];
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="?section=overview">Who Uses OpinionHub.ng?</a></li>
                    <?php if ($section !== 'overview'): ?>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $current_section['title']; ?></li>
                    <?php endif; ?>
                </ol>
            </nav>

            <h1 class="mb-4"><?php echo $current_section['title']; ?></h1>

            <div class="content">
                <?php echo $current_section['content']; ?>
            </div>

            <?php if ($section !== 'overview'): ?>
            <div class="mt-5 text-center">
                <a href="?section=overview" class="btn btn-outline-primary me-2">← Back to Overview</a>
                <a href="<?php echo SITE_URL; ?>register.php" class="btn btn-primary">Get Started Today</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s;
}
.card:hover {
    transform: translateY(-5px);
}
</style>

<?php include_once 'footer.php'; ?>


