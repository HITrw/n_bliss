<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$pageTitle = 'Settings';
$currentPage = 'settings';

// Get database instance
$db = Database::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['updateSiteSettings'])) {
        // Update site settings
        // TODO: Implement site settings update logic
    } elseif (isset($_POST['updateEmailSettings'])) {
        // Update email settings
        // TODO: Implement email settings update logic
    }
}

// Include header
require_once 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Settings</h1>
    </div>

    <div class="row">
        <!-- Site Settings Card -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Site Settings</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="siteName" class="form-label">Site Name</label>
                            <input type="text" class="form-control" id="siteName" name="siteName" value="<?= SITE_NAME ?>">
                        </div>
                        <div class="mb-3">
                            <label for="siteDescription" class="form-label">Site Description</label>
                            <textarea class="form-control" id="siteDescription" name="siteDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select class="form-select" id="timezone" name="timezone">
                                <?php
                                $timezones = DateTimeZone::listIdentifiers();
                                foreach ($timezones as $tz) {
                                    echo "<option value=\"$tz\"" . ($tz === date_default_timezone_get() ? ' selected' : '') . ">$tz</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" name="updateSiteSettings" class="btn btn-primary">Save Site Settings</button>
                    </form>
                </div>
            </div>
        </div>

<script>
function setTheme(theme) {
    fetch('../api/dashboard/toggle-theme.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ theme: theme })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to apply the new theme
            window.location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

<?php require_once 'footer.php'; ?>
