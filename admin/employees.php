<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$pageTitle = 'Employees';
$currentPage = 'employees';

// Get database instance
$db = Database::getInstance();

// Define roles
$roles = ['admin' => 'Admin', 'staff' => 'Staff'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($_POST['action'] === 'create') {
            // Create new employee with default password
            $password = password_hash('password123', PASSWORD_DEFAULT);
            $data = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'is_active' => $is_active
            ];
            $db->insert('employees', $data);
        } elseif ($_POST['action'] === 'update') {
            $id = $_POST['id'] ?? 0;
            $data = [
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'is_active' => $is_active
            ];
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            $db->update('employees', $data, 'id = ?', [$id]);
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'] ?? 0;
            // Don't delete the last admin
            $adminCount = $db->fetch("SELECT COUNT(*) as count FROM employees WHERE role = 'admin' AND id != ?", [$id])['count'];
            if ($adminCount > 0) {
                $db->delete('employees', 'id = ?', [$id]);
            }
        }
        
        header('Location: employees.php');
        exit;
    }
}

// Get all employees
$employees = $db->fetchAll("
    SELECT 
        id, name, email, role, is_active, 
        DATE_FORMAT(last_login, '%Y-%m-%d %H:%i') as last_login,
        DATE_FORMAT(created_at, '%Y-%m-%d') as created_at 
    FROM employees 
    ORDER BY name
");

include 'header.php';
?>

<div class="container-fluid py-4">
    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeModalLabel">Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="employees.php" method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <?php foreach ($roles as $value => $label): ?>
                                            <option value="<?= $value ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Employees</h5>
        </div>
        <div class="card-body">            <div class="table-responsive">
                <table id="employeesTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><?= h($employee['name']) ?></td>
                                <td><?= h($employee['email']) ?></td>
                                <td><span class="badge bg-info"><?= h($roles[$employee['role']]) ?></span></td>
                                <td>
                                    <?php if ($employee['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($employee['last_login'] ?? 'Never') ?></td>
                                <td><?= h($employee['created_at']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-primary edit-employee" 
                                                data-id="<?= $employee['id'] ?>"
                                                data-name="<?= h($employee['name']) ?>"
                                                data-email="<?= h($employee['email']) ?>"
                                                data-role="<?= h($employee['role']) ?>"
                                                data-is-active="<?= $employee['is_active'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($employee['id'] != $_SESSION['admin_id']): ?>
                                            <button type="button" class="btn btn-danger delete-employee"
                                                    data-id="<?= $employee['id'] ?>"
                                                    data-name="<?= h($employee['name']) ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Button -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
        Add Employee
    </button>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="employees.php" method="POST" id="editForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <?php foreach ($roles as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Password (Leave empty to keep current)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Employee Modal -->
<div class="modal fade" id="deleteEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete employee "<span id="deleteEmployeeName"></span>"?</p>
                <form action="employees.php" method="POST" id="deleteForm">
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
    // Initialize Employees DataTable
    $('#employeesTable').DataTable({
        pageLength: 1000,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: -1 }
        ]
    });

    // Edit Employee
    const editButtons = document.querySelectorAll('.edit-employee');
    const editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const data = this.dataset;
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_email').value = data.email;
            document.getElementById('edit_role').value = data.role;
            document.getElementById('edit_is_active').checked = data.isActive === '1';
            editModal.show();
        });
    });

    // Delete Employee
    const deleteButtons = document.querySelectorAll('.delete-employee');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteEmployeeModal'));
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const data = this.dataset;
            document.getElementById('delete_id').value = data.id;
            document.getElementById('deleteEmployeeName').textContent = data.name;
            deleteModal.show();
        });
    });
});
</script>

<?php include 'footer.php'; ?>
