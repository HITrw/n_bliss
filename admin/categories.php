<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$pageTitle = 'Categories';
$currentPage = 'categories';

// Get database instance
$db = Database::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $name = $_POST['name'] ?? '';
        $slug = strtolower(str_replace(' ', '-', $name));
        $description = $_POST['description'] ?? '';
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
        
        if ($_POST['action'] === 'create') {
            // Create new category
            $data = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'parent_id' => $parent_id
            ];
            $db->insert('categories', $data);
        } elseif ($_POST['action'] === 'update') {
            // Update existing category
            $id = $_POST['id'] ?? 0;
            $data = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'parent_id' => $parent_id
            ];
            $db->update('categories', $data, 'id = ?', [$id]);
        } elseif ($_POST['action'] === 'delete') {
            // Delete category
            $id = $_POST['id'] ?? 0;
            $db->delete('categories', 'id = ?', [$id]);
        }
        
        // Redirect to prevent form resubmission
        header('Location: categories.php');
        exit;
    }
}

// Get all categories
$categories = $db->fetchAll("
    SELECT c.*, p.name as parent_name 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY CASE WHEN c.parent_id IS NULL THEN 0 ELSE 1 END, c.name
");

// Get parent categories for dropdown
$parentCategories = $db->fetchAll("
    SELECT id, name 
    FROM categories 
    WHERE parent_id IS NULL 
    ORDER BY name
");

include 'header.php';
?>

<div class="container-fluid py-4">
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="categories.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="parent_id" class="form-label">Parent Category</label>
                                    <select class="form-select" id="parent_id" name="parent_id">
                                        <option value="">None</option>
                                        <?php foreach ($parentCategories as $parent): ?>
                                            <option value="<?= $parent['id'] ?>"><?= h($parent['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Categories</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Parent Category</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= h($category['name']) ?></td>
                                <td><?= h($category['parent_name'] ?? 'None') ?></td>
                                <td><?= h($category['description']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-primary edit-category" 
                                                data-id="<?= $category['id'] ?>"
                                                data-name="<?= h($category['name']) ?>"
                                                data-description="<?= h($category['description']) ?>"
                                                data-parent-id="<?= $category['parent_id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger delete-category"
                                                data-id="<?= $category['id'] ?>"
                                                data-name="<?= h($category['name']) ?>">
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

    <!-- Add Button -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        Add Category
    </button>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="categories.php" method="POST" enctype="multipart/form-data" id="editForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_parent_id" class="form-label">Parent Category (Optional)</label>
                        <select class="form-select" id="edit_parent_id" name="parent_id">
                            <option value="">None (Top Level)</option>
                            <?php foreach ($parentCategories as $parent): ?>
                                <option value="<?= $parent['id'] ?>"><?= h($parent['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the category "<span id="deleteCategoryName"></span>"?</p>
                <form action="categories.php" method="POST" id="deleteForm">
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
    // Edit Category
    const editButtons = document.querySelectorAll('.edit-category');
    const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const data = this.dataset;
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_description').value = data.description;
            document.getElementById('edit_parent_id').value = data.parentId || '';
            editModal.show();
        });
    });

    // Delete Category
    const deleteButtons = document.querySelectorAll('.delete-category');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const data = this.dataset;
            document.getElementById('delete_id').value = data.id;
            document.getElementById('deleteCategoryName').textContent = data.name;
            deleteModal.show();
        });
    });
});
</script>

<?php include 'footer.php'; ?>
