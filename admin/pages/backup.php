<?php
/**
 * Admin Database Backup & Restore Page
 */

// Define backup directory
$backupDir = dirname(__DIR__, 2) . '/backups';

// Create backup directory if it doesn't exist
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Get list of existing backups
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filePath = $backupDir . '/' . $file;
            $backups[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'date' => filemtime($filePath)
            ];
        }
    }
}

// Get database size and table info
$dbInfo = [];
$totalSize = 0;
$tableCount = 0;

if ($pdo) {
    try {
        // Get table sizes using SHOW TABLE STATUS (more reliable than information_schema)
        $stmt = $pdo->query("SHOW TABLE STATUS");
        $tables = $stmt->fetchAll();
        
        foreach ($tables as $table) {
            $size = ($table['Data_length'] + $table['Index_length']) / 1024; // KB
            $dbInfo[] = [
                'name' => $table['Name'],
                'rows' => $table['Rows'],
                'size_kb' => round($size, 2)
            ];
            
            $totalSize += $size;
            $tableCount++;
        }
        
        // Sort by size descending
        usort($dbInfo, function($a, $b) {
            return $b['size_kb'] <=> $a['size_kb'];
        });
        
    } catch (PDOException $e) {
        // Ignore
    }
}

// Format file size nicely
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>

<div class="page-header">
    <h1 class="page-title">Database Backup & Restore</h1>
    <p class="page-subtitle">Manage your database backups to protect your data</p>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card info">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="bi bi-database"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo $tableCount; ?></div>
        <div class="stat-label">Database Tables</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="bi bi-hdd"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo round($totalSize / 1024, 2); ?> MB</div>
        <div class="stat-label">Database Size</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="bi bi-archive"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo count($backups); ?></div>
        <div class="stat-label">Saved Backups</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="bi bi-clock-history"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo !empty($backups) ? date('M d', $backups[0]['date']) : 'N/A'; ?></div>
        <div class="stat-label">Last Backup</div>
    </div>
</div>

<!-- Actions Row -->
<div class="row g-4 mt-2">
    <!-- Create Backup Card -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="bi bi-cloud-arrow-up me-2"></i>Create Backup
                </h3>
            </div>
            <div class="admin-card-body">
                <p style="color: var(--admin-text-muted); margin-bottom: 1.5rem;">
                    Create a complete backup of your database including all tables, data, and structure. 
                    Backups are saved as SQL files that can be restored at any time.
                </p>
                
                <div class="backup-options" style="background: var(--admin-dark); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem;">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="includeDropTables" checked>
                        <label class="form-check-label" for="includeDropTables">
                            Include DROP TABLE statements
                        </label>
                        <small class="d-block text-muted mt-1">Recommended for clean restores</small>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="includeCreateDb" checked>
                        <label class="form-check-label" for="includeCreateDb">
                            Include CREATE DATABASE statement
                        </label>
                        <small class="d-block text-muted mt-1">Include database creation script</small>
                    </div>
                </div>
                
                <button type="button" class="btn-admin-primary w-100" id="createBackupBtn" onclick="createBackup()">
                    <i class="bi bi-download me-2"></i>Create Backup Now
                </button>
            </div>
        </div>
    </div>
    
    <!-- Restore Backup Card -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="bi bi-cloud-arrow-down me-2"></i>Restore Backup
                </h3>
            </div>
            <div class="admin-card-body">
                <p style="color: var(--admin-text-muted); margin-bottom: 1.5rem;">
                    Upload and restore a database backup from a SQL file. 
                    <strong class="text-warning">Warning:</strong> This will replace all existing data!
                </p>
                
                <div class="upload-zone" id="uploadZone" style="border: 2px dashed var(--admin-dark-tertiary); border-radius: 12px; padding: 2rem; text-align: center; transition: all 0.3s ease; cursor: pointer;">
                    <input type="file" id="restoreFile" accept=".sql" style="display: none;">
                    <i class="bi bi-cloud-upload" style="font-size: 2.5rem; color: var(--admin-text-muted);"></i>
                    <p style="color: var(--admin-text-muted); margin: 1rem 0 0.5rem;">
                        Drag & drop SQL file here or click to browse
                    </p>
                    <small class="text-muted">Only .sql files are accepted</small>
                </div>
                
                <div id="selectedFile" style="display: none; margin-top: 1rem; padding: 1rem; background: var(--admin-dark); border-radius: 8px;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-code" style="font-size: 1.5rem; color: var(--admin-primary);"></i>
                            <div>
                                <div id="fileName" style="font-weight: 500;"></div>
                                <small id="fileSize" class="text-muted"></small>
                            </div>
                        </div>
                        <button type="button" class="btn-icon" onclick="clearFile()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                
                <button type="button" class="btn-admin-primary w-100 mt-3" id="restoreBtn" onclick="restoreBackup()" disabled>
                    <i class="bi bi-upload me-2"></i>Restore Database
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Existing Backups -->
<div class="admin-card mt-4">
    <div class="admin-card-header">
        <h3 class="admin-card-title">
            <i class="bi bi-archive me-2"></i>Saved Backups
        </h3>
        <span class="badge bg-secondary"><?php echo count($backups); ?> files</span>
    </div>
    <div class="admin-card-body p-0">
        <?php if (empty($backups)): ?>
        <div class="empty-state">
            <i class="bi bi-archive"></i>
            <h3>No backups yet</h3>
            <p>Create your first backup to see it listed here.</p>
        </div>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $backup): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-code" style="font-size: 1.25rem; color: var(--admin-primary);"></i>
                            <strong><?php echo sanitize($backup['name']); ?></strong>
                        </div>
                    </td>
                    <td><?php echo formatFileSize($backup['size']); ?></td>
                    <td><?php echo date('M d, Y g:i A', $backup['date']); ?></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="includes/backup_download.php?file=<?php echo urlencode($backup['name']); ?>" 
                               class="btn btn-sm" 
                               style="background: rgba(59, 130, 246, 0.1); color: var(--admin-info);"
                               title="Download">
                                <i class="bi bi-download"></i>
                            </a>
                            <button type="button" 
                                    class="btn btn-sm" 
                                    style="background: rgba(16, 185, 129, 0.1); color: var(--admin-success);"
                                    onclick="restoreFromSaved('<?php echo sanitize($backup['name']); ?>')"
                                    title="Restore">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            <button type="button" 
                                    class="btn btn-sm" 
                                    style="background: rgba(239, 68, 68, 0.1); color: var(--admin-danger);"
                                    onclick="deleteBackup('<?php echo sanitize($backup['name']); ?>')"
                                    title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Database Tables Info -->
<div class="admin-card mt-4">
    <div class="admin-card-header">
        <h3 class="admin-card-title">
            <i class="bi bi-table me-2"></i>Database Tables
        </h3>
    </div>
    <div class="admin-card-body p-0">
        <?php if (empty($dbInfo)): ?>
        <div class="empty-state">
            <i class="bi bi-database-x"></i>
            <h3>No tables found</h3>
            <p>Unable to retrieve database information.</p>
        </div>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th>Rows</th>
                    <th>Size</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dbInfo as $table): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-table" style="color: var(--admin-text-muted);"></i>
                            <?php echo sanitize($table['name']); ?>
                        </div>
                    </td>
                    <td><?php echo number_format($table['rows']); ?></td>
                    <td><?php echo $table['size_kb']; ?> KB</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--admin-dark-secondary); border: 1px solid var(--admin-dark-tertiary);">
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h4 id="progressTitle">Processing...</h4>
                <p id="progressText" class="text-muted mb-0">Please wait while the operation completes.</p>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Restore Modal -->
<div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--admin-dark-secondary); border: 1px solid var(--admin-dark-tertiary);">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Confirm Restore
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to restore the database from this backup?</p>
                <div class="alert" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--admin-danger);">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <strong>Warning:</strong> This action will replace all existing data and cannot be undone!
                </div>
                <p class="mb-0"><strong>File:</strong> <span id="restoreFileName"></span></p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRestoreBtn">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Yes, Restore Database
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.upload-zone:hover,
.upload-zone.dragover {
    border-color: var(--admin-primary);
    background: rgba(99, 102, 241, 0.05);
}

.form-check-input:checked {
    background-color: var(--admin-primary);
    border-color: var(--admin-primary);
}

.form-check-label {
    color: var(--admin-text);
}

.backup-options .text-muted,
.backup-options small {
    color: rgba(255, 255, 255, 0.5) !important;
    font-size: 0.8rem;
}

.upload-zone .text-muted,
.upload-zone small {
    color: rgba(255, 255, 255, 0.5) !important;
}

#selectedFile .text-muted,
#selectedFile small {
    color: rgba(255, 255, 255, 0.5) !important;
}

#progressText.text-muted {
    color: rgba(255, 255, 255, 0.6) !important;
}
</style>

<script>
let selectedFile = null;
let restoreFromFile = null;

// Upload zone interactions
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('restoreFile');

uploadZone.addEventListener('click', () => fileInput.click());

uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('dragover');
});

uploadZone.addEventListener('dragleave', () => {
    uploadZone.classList.remove('dragover');
});

uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFileSelect(files[0]);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFileSelect(e.target.files[0]);
    }
});

function handleFileSelect(file) {
    if (!file.name.endsWith('.sql')) {
        alert('Please select a valid SQL file.');
        return;
    }
    
    selectedFile = file;
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatBytes(file.size);
    document.getElementById('selectedFile').style.display = 'block';
    document.getElementById('restoreBtn').disabled = false;
}

function clearFile() {
    selectedFile = null;
    fileInput.value = '';
    document.getElementById('selectedFile').style.display = 'none';
    document.getElementById('restoreBtn').disabled = true;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Create Backup
async function createBackup() {
    const btn = document.getElementById('createBackupBtn');
    const includeDropTables = document.getElementById('includeDropTables').checked;
    const includeCreateDb = document.getElementById('includeCreateDb').checked;
    
    showProgress('Creating Backup...', 'Exporting database structure and data...');
    
    try {
        const formData = new FormData();
        formData.append('include_drop', includeDropTables ? '1' : '0');
        formData.append('include_create_db', includeCreateDb ? '1' : '0');
        
        const response = await fetch('includes/backup_create.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        hideProgress();
        
        if (result.success) {
            showToast('success', 'Backup created successfully: ' + result.filename);
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('error', 'Error: ' + result.error);
        }
    } catch (error) {
        hideProgress();
        showToast('error', 'Error creating backup: ' + error.message);
    }
}

// Restore from uploaded file
function restoreBackup() {
    if (!selectedFile) return;
    
    restoreFromFile = null;
    document.getElementById('restoreFileName').textContent = selectedFile.name;
    
    const modal = new bootstrap.Modal(document.getElementById('confirmRestoreModal'));
    modal.show();
    
    document.getElementById('confirmRestoreBtn').onclick = async () => {
        modal.hide();
        showProgress('Restoring Database...', 'Please wait, this may take a few moments...');
        
        try {
            const formData = new FormData();
            formData.append('backup_file', selectedFile);
            
            const response = await fetch('includes/backup_restore.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            hideProgress();
            
            if (result.success) {
                showToast('success', 'Database restored successfully!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('error', 'Error: ' + result.error);
            }
        } catch (error) {
            hideProgress();
            showToast('error', 'Error restoring database: ' + error.message);
        }
    };
}

// Restore from saved backup
function restoreFromSaved(filename) {
    restoreFromFile = filename;
    document.getElementById('restoreFileName').textContent = filename;
    
    const modal = new bootstrap.Modal(document.getElementById('confirmRestoreModal'));
    modal.show();
    
    document.getElementById('confirmRestoreBtn').onclick = async () => {
        modal.hide();
        showProgress('Restoring Database...', 'Please wait, this may take a few moments...');
        
        try {
            const formData = new FormData();
            formData.append('filename', filename);
            
            const response = await fetch('includes/backup_restore.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            hideProgress();
            
            if (result.success) {
                showToast('success', 'Database restored successfully!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('error', 'Error: ' + result.error);
            }
        } catch (error) {
            hideProgress();
            showToast('error', 'Error restoring database: ' + error.message);
        }
    };
}

// Delete backup
async function deleteBackup(filename) {
    if (!confirm('Are you sure you want to delete this backup?\n\n' + filename)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('filename', filename);
        
        const response = await fetch('includes/backup_delete.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('success', 'Backup deleted successfully');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', 'Error: ' + result.error);
        }
    } catch (error) {
        showToast('error', 'Error deleting backup: ' + error.message);
    }
}

// Progress modal helpers
function showProgress(title, text) {
    document.getElementById('progressTitle').textContent = title;
    document.getElementById('progressText').textContent = text;
    const modal = new bootstrap.Modal(document.getElementById('progressModal'));
    modal.show();
}

function hideProgress() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('progressModal'));
    if (modal) modal.hide();
}

// Toast notification (you can use your existing toast system)
function showToast(type, message) {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease;';
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
</style>
