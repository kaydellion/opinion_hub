<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('client');

$user = getCurrentUser();

// Get filter parameters
$filters = [];
if (isset($_GET['age_min'])) $filters['age_min'] = (int)$_GET['age_min'];
if (isset($_GET['age_max'])) $filters['age_max'] = (int)$_GET['age_max'];
if (isset($_GET['gender'])) $filters['gender'] = sanitize($_GET['gender']);
if (isset($_GET['state'])) $filters['state'] = sanitize($_GET['state']);
if (isset($_GET['education_level'])) $filters['education_level'] = sanitize($_GET['education_level']);

// Get filtered agents
$agents = getFilteredAgents($filters);

// Get unique states for filter dropdown
$states_query = $conn->query("SELECT DISTINCT state FROM agents WHERE state IS NOT NULL AND state != '' ORDER BY state");

$page_title = 'Browse Agents';
include '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Agents</h6>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <div class="mb-3">
                            <label class="form-label">Age Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="number" name="age_min" class="form-control form-control-sm" 
                                           placeholder="Min" value="<?= $_GET['age_min'] ?? '' ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="age_max" class="form-control form-control-sm" 
                                           placeholder="Max" value="<?= $_GET['age_max'] ?? '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="male" <?= ($_GET['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($_GET['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other" <?= ($_GET['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">State</label>
                            <select name="state" class="form-select form-select-sm">
                                <option value="">All States</option>
                                <?php while ($s = $states_query->fetch_assoc()): ?>
                                    <option value="<?= $s['state'] ?>" <?= ($_GET['state'] ?? '') === $s['state'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['state']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Education Level</label>
                            <select name="education_level" class="form-select form-select-sm">
                                <option value="">All Levels</option>
                                <option value="primary" <?= ($_GET['education_level'] ?? '') === 'primary' ? 'selected' : '' ?>>Primary</option>
                                <option value="secondary" <?= ($_GET['education_level'] ?? '') === 'secondary' ? 'selected' : '' ?>>Secondary</option>
                                <option value="tertiary" <?= ($_GET['education_level'] ?? '') === 'tertiary' ? 'selected' : '' ?>>Tertiary</option>
                                <option value="postgraduate" <?= ($_GET['education_level'] ?? '') === 'postgraduate' ? 'selected' : '' ?>>Postgraduate</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                            <i class="fas fa-search me-2"></i>Apply Filters
                        </button>
                        <a href="agents.php" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="fas fa-times me-2"></i>Clear Filters
                        </a>
                    </form>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Export Options</h6>
                    <a href="../export.php?type=agents" class="btn btn-sm btn-outline-success w-100">
                        <i class="fas fa-download me-2"></i>Export All Agents (CSV)
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Available Agents
                        <span class="badge bg-primary ms-2"><?= count($agents) ?> found</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($agents) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Location</th>
                                        <th>Education</th>
                                        <th>Completed Tasks</th>
                                        <th>Reward Preference</th>
                                        <th>Contact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($agents as $agent): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($agent['name']) ?></td>
                                            <td><?= $agent['age'] ?? 'N/A' ?></td>
                                            <td><?= ucfirst($agent['gender'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($agent['state'] ?? 'N/A') ?>, <?= htmlspecialchars($agent['lga'] ?? '') ?></td>
                                            <td><?= ucfirst($agent['education_level'] ?? 'N/A') ?></td>
                                            <td><?= number_format($agent['tasks_completed']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $agent['reward_preference'] === 'cash' ? 'success' : 'info' ?>">
                                                    <?= ucfirst($agent['reward_preference']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="mailto:<?= $agent['email'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-envelope"></i>
                                                </a>
                                                <a href="tel:<?= $agent['phone'] ?>" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-phone"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No agents found matching your filters. Try adjusting your criteria.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="alert alert-info mt-4">
                <h6><i class="fas fa-lightbulb me-2"></i>About Agent Targeting</h6>
                <p class="mb-0 small">
                    Use demographic filters to find agents that match your target audience. When creating polls, you can 
                    specify demographic criteria to automatically assign tasks to relevant agents. This ensures your 
                    surveys reach the right respondents for more accurate data.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
