<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$pageTitle = 'Tables';
$currentPage = 'tables';

// Get database instance
$db = Database::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $table_number = $_POST['table_number'] ?? '';
        
        if ($_POST['action'] === 'create') {
            // Create new table
            $db->insert('tables', ['table_number' => $table_number]);
        } elseif ($_POST['action'] === 'update') {
            // Update existing table
            $id = $_POST['id'] ?? 0;
            $db->update('tables', 
                ['table_number' => $table_number], 
                'id = ?', 
                [$id]
            );
        } elseif ($_POST['action'] === 'delete') {
            // Delete table
            $id = $_POST['id'] ?? 0;
            $db->delete('tables', 'id = ?', [$id]);
        }
        
        // Redirect to prevent form resubmission
        header('Location: tables.php');
        exit;
    }
}

// Get all tables
$tables = $db->fetchAll("SELECT id, table_number FROM tables ORDER BY table_number");

include 'header.php';
?>

<div class="container-fluid py-4">
    <!-- Add Table Button -->
    <div class="mb-4">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTableModal">
            Add Table
        </button>
    </div>

    <!-- Tables List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Tables</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Table Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $table): ?>
                            <tr>
                                <td><?= h($table['id']) ?></td>
                                <td><?= h($table['table_number']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-primary edit-table" 
                                                data-id="<?= $table['id'] ?>"
                                                data-table-number="<?= h($table['table_number']) ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger delete-table"
                                                data-id="<?= $table['id'] ?>"
                                                data-table-number="<?= h($table['table_number']) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Table Modal -->
<div class="modal fade" id="addTableModal" tabindex="-1" aria-labelledby="addTableModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTableModalLabel">Add New Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="tables.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="table_number" class="form-label">Table Number</label>
                                <input type="text" class="form-control" id="table_number" name="table_number" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Table</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Table Modal -->
<div class="modal fade" id="editTableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="tables.php" method="POST" id="editForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_table_number" class="form-label">Table Number</label>
                        <input type="text" class="form-control" id="edit_table_number" name="table_number" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Table</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Table Modal -->
<div class="modal fade" id="deleteTableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete table "<span id="deleteTableNumber"></span>"?</p>
                <form action="tables.php" method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Table
    const editButtons = document.querySelectorAll('.edit-table');
    const editModal = new bootstrap.Modal(document.getElementById('editTableModal'));
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const data = this.dataset;
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_table_number').value = data.tableNumber;
            editModal.show();
        });
    });

    // Delete Table
    const deleteButtons = document.querySelectorAll('.delete-table');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteTableModal'));
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const data = this.dataset;
            document.getElementById('delete_id').value = data.id;
            document.getElementById('deleteTableNumber').textContent = data.tableNumber;
            deleteModal.show();
        });
    });
});
</script>

<?php include 'footer.php'; ?>
