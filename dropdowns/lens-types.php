<?php
/**
 * Lens Type Management Page
 * Customer Management System
 */

$pageTitle = "Lens Type Management";
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();
$error = '';

// Add Lens Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add'])) {
    if (!verifyCsrfToken()) {
        $error = "Invalid security token.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if (empty($name)) {
            $error = "Lens Type name cannot be empty.";
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO lens_types (name, status) VALUES (?, ?)");
                $stmt->execute([$name, $status]);
                setFlash('success', "Lens Type '{$name}' added successfully.");
                header("Location: lens-types.php");
                exit();
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Lens Type '{$name}' already exists!";
                } else {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

// Edit Lens Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit'])) {
    if (!verifyCsrfToken()) {
        $error = "Invalid security token.";
    } else {
        $id = (int)($_POST['lens_type_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if ($id > 0 && !empty($name)) {
            try {
                $stmt = $db->prepare("UPDATE lens_types SET name = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $status, $id]);
                setFlash('success', "Lens Type updated successfully.");
                header("Location: lens-types.php");
                exit();
            } catch (PDOException $e) {
                $error = "Database error or duplicate lens type name.";
            }
        }
    }
}

// Toggle Status (Activate / Deactivate)
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT status FROM lens_types WHERE id = ?");
    $stmt->execute([$id]);
    $lens = $stmt->fetch();
    if ($lens) {
        $newStatus = ($lens['status'] === 'active') ? 'inactive' : 'active';
        $updateStmt = $db->prepare("UPDATE lens_types SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $id]);
        setFlash('success', "Lens Type status updated to {$newStatus}.");
    }
    header("Location: lens-types.php");
    exit();
}

// Delete Lens Type
if (isset($_GET['delete']) && isset($_GET['id'])) {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Invalid security token.');
    } else {
        $id = (int)$_GET['id'];
        $stmt = $db->prepare("DELETE FROM lens_types WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', "Lens Type deleted successfully.");
    }
    header("Location: lens-types.php");
    exit();
}

// Fetch all lens types
$lensTypes = $db->query("SELECT * FROM lens_types ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Lens Type Management</h3>
        <p class="text-muted m-0">Manage optical lens options dynamically for customer billing</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addLensModal">
        <i class="bi bi-plus-lg me-1"></i> Add Lens Type
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
                    <th>Lens Type Name</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lensTypes)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No Lens Types found. Add one above.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lensTypes as $index => $row): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($row['name']); ?></td>
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
                                <a href="lens-types.php?toggle=1&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-<?php echo ($row['status'] === 'active') ? 'warning' : 'success'; ?> me-1" title="Toggle Status">
                                    <?php echo ($row['status'] === 'active') ? 'Deactivate' : 'Activate'; ?>
                                </a>

                                <!-- Edit Modal Trigger -->
                                <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#editLensModal<?php echo $row['id']; ?>" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <!-- Delete Link -->
                                <a href="lens-types.php?delete=1&id=<?php echo $row['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this Lens Type?');" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <!-- Edit Modal for Lens Type #<?php echo $row['id']; ?> -->
                        <div class="modal fade" id="editLensModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="lens-types.php" method="POST">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action_edit" value="1">
                                        <input type="hidden" name="lens_type_id" value="<?php echo $row['id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit Lens Type</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Lens Type Name</label>
                                                <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($row['name']); ?>">
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
                                            <button type="submit" class="btn btn-primary">Update Lens Type</button>
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

<!-- Add Lens Modal -->
<div class="modal fade" id="addLensModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="lens-types.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action_add" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Lens Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Lens Type Name</label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g. Blue Cut, Progressive, Photochromic">
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
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Save Lens Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
