<?php
require_once __DIR__ . '/../includes/config.php';
include '../includes/db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}

$backup_success = '';
$backup_error = '';
$backup_file = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup_db'])) {
    $db_host = DB_HOST;
    $db_user = DB_USER;
    $db_pass = DB_PASS;
    $db_name = DB_NAME;
    $timestamp = date('Y-m-d_H-i-s');
    $backup_file = __DIR__ . '/../database/canteen_db_' . $timestamp . '.sql';
    $backup_file_rel = 'database/canteen_db_' . $timestamp . '.sql';
    $mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe';
    $cmd = "\"$mysqldump\" --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} > \"{$backup_file}\"";
    $output = null;
    $result = null;
    @exec($cmd, $output, $result);
    if ($result === 0 && file_exists($backup_file)) {
        $backup_success = 'Backup created successfully!';
    } else {
        $backup_error = 'Backup failed. Please check server permissions and mysqldump path.';
    }
}
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
    <div class="dashboard-section-title mb-3">Backup Database</div>
    <form method="post">
        <button type="submit" name="backup_db" class="btn btn-primary mb-3"><i class="fas fa-database me-2"></i>Create Backup</button>
    </form>
    <?php if ($backup_success): ?>
        <div class="alert alert-success"><?= $backup_success ?>
            <?php if ($backup_file && file_exists($backup_file)): ?>
                <br><a href="../<?= $backup_file_rel ?>" download>Download Backup</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($backup_error): ?>
        <div class="alert alert-danger"><?= $backup_error ?></div>
    <?php endif; ?>
    <div class="alert alert-info">This will create a backup of the current database and save it in the <b>/database</b> folder.</div>

    <h5 class="mt-4">Existing Backups</h5>
    <div class="dashboard-table table-responsive mb-4">
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Download</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $backup_dir = realpath(__DIR__ . '/../database');
                $files = glob($backup_dir . '/*.sql');
                usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
                foreach ($files as $file):
                    $basename = basename($file);
                    $size = filesize($file);
                    $size_str = $size > 1048576 ? round($size/1048576,2).' MB' : ($size > 1024 ? round($size/1024,2).' KB' : $size.' B');
                ?>
                <tr>
                    <td><?= htmlspecialchars($basename) ?></td>
                    <td><?= $size_str ?></td>
                    <td><a href="../database/<?= rawurlencode($basename) ?>" download class="btn btn-sm btn-success"><i class="fa fa-download"></i> Download</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($files)): ?>
                <tr><td colspan="3" class="text-center text-muted">No backups found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div> 