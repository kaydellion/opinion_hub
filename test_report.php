<?php
include_once 'connect.php';
include_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: signin.php");
    exit;
}

$user = getCurrentUser();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Report Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Test Poll Report</h1>
        <p>Logged in as: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>

        <form id="testReportForm">
            <input type="hidden" name="action" value="report_poll">
            <input type="hidden" name="poll_id" value="1">
            <div class="mb-3">
                <label for="testReason" class="form-label">Reason for reporting</label>
                <select class="form-select" id="testReason" name="reason" required>
                    <option value="">Select a reason...</option>
                    <option value="spam">Spam or misleading content</option>
                    <option value="inappropriate">Inappropriate content</option>
                    <option value="harassment">Harassment or hate speech</option>
                    <option value="copyright">Copyright violation</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="testDescription" class="form-label">Additional details (optional)</label>
                <textarea class="form-control" id="testDescription" name="description" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Submit Test Report</button>
        </form>

        <div id="result" class="mt-3"></div>
    </div>

    <script>
    document.getElementById('testReportForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        console.log('Test report form data:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }

        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="alert alert-info">Submitting report...</div>';

        fetch('<?php echo SITE_URL; ?>actions.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            console.log('Test report response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Test report raw response:', text);
            try {
                const data = JSON.parse(text);
                console.log('Test report parsed data:', data);

                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    document.getElementById('testReportForm').reset();
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Failed to submit report') + '</div>';
                }
            } catch (e) {
                console.error('Test report JSON parse error:', e);
                resultDiv.innerHTML = '<div class="alert alert-danger">Server returned invalid response: ' + text + '</div>';
            }
        })
        .catch(error => {
            console.error('Test report network error:', error);
            resultDiv.innerHTML = '<div class="alert alert-danger">Network error: ' + error.message + '</div>';
        });
    });
    </script>
</body>
</html>
