<?php
/**
 * Power Management Page
 * Customer Management System
 */

$pageTitle = "Power Management";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();
$error = '';

// Add Power
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add'])) {
    if (!verifyCsrfToken()) {
        $error = "Invalid security token.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if (empty($name)) {
            $error = "Power value cannot be empty.";
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO powers (name, status) VALUES (?, ?)");
                $stmt->execute([$name, $status]);
                setFlash('success', "Power value '{$name}' added successfully.");
                header("Location: powers.php");
                exit();
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Power value '{$name}' already exists!";
                } else {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

// Edit Power
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit'])) {
    if (!verifyCsrfToken()) {
        $error = "Invalid security token.";
    } else {
        $id = (int)($_POST['power_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if ($id > 0 && !empty($name)) {
            try {
                $stmt = $db->prepare("UPDATE powers SET name = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $status, $id]);
                setFlash('success', "Power updated successfully.");
                header("Location: powers.php");
                exit();
            } catch (PDOException $e) {
                $error = "Database error or duplicate value.";
            }
        }
    }
}

// Toggle Status (Activate / Deactivate)
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT status FROM powers WHERE id = ?");
    $stmt->execute([$id]);
    $power = $stmt->fetch();
    if ($power) {
        $newStatus = ($power['status'] === 'active') ? 'inactive' : 'active';
        $updateStmt = $db->prepare("UPDATE powers SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $id]);
        setFlash('success', "Power status updated to {$newStatus}.");
    }
    header("Location: powers.php");
    exit();
}

// Delete Power
if (isset($_GET['delete']) && isset($_GET['id'])) {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Invalid security token.');
    } else {
        $id = (int)$_GET['id'];
        $stmt = $db->prepare("DELETE FROM powers WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', "Power deleted successfully.");
    }
    header("Location: powers.php");
    exit();
}

// Fetch all powers
$powers = $db->query("SELECT * FROM powers ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Optical Power Management</h3>
        <p class="text-muted m-0">Dynamically add, edit, activate or deactivate power options for customer entries</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addPowerModal">
        <i class="bi bi-plus-lg me-1"></i> Add Power Option
    </button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 mb-3"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Power Value</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($powers)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No power options found. Add one above.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($powers as $index => $row): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td class="fw-bold font-monospace text-primary fs-6"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td>
                                <?php if ($row['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i> Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-x-circle me-1"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                            <td class="text-end">
                                <!-- Status Toggle Button -->
                                <a href="powers.php?toggle=1&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-<?php echo ($row['status'] === 'active') ? 'warning' : 'success'; ?> me-1" title="Toggle Active/Inactive">
                                    <?php echo ($row['status'] === 'active') ? 'Deactivate' : 'Activate'; ?>
                                </a>

                                <!-- Edit Modal Trigger -->
                                <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#editPowerModal<?php echo $row['id']; ?>" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <!-- Delete Link -->
                                <a href="powers.php?delete=1&id=<?php echo $row['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this Power option?');" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <!-- Edit Modal for Power #<?php echo $row['id']; ?> -->
                        <div class="modal fade" id="editPowerModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="powers.php" method="POST">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action_edit" value="1">
                                        <input type="hidden" name="power_id" value="<?php echo $row['id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit Power Option</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Power Value</label>
                                                <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($row['name']); ?>" placeholder="e.g. +1.50 or -2.00">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status">
                                                    <option value="active" <?php echo $row['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $row['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Update Power</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Power Modal -->
<div class="modal fade" id="addPowerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="powers.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action_add" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Optical Power Option</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Power Value</label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g. +2.75 or -1.25">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Initial Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Save Power</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
