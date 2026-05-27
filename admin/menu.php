<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$pageTitle = 'Menu Items';
$currentPage = 'menu';

// Get database instance
$db = Database::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? 0;
        $description = $_POST['description'] ?? '';
        $category_id = $_POST['category_id'] ?? null;
        $is_drink = isset($_POST['is_drink']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $slug = strtolower(str_replace(' ', '-', $name));
        
        // Handle file upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/menu/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $image_path = 'uploads/menu/' . $fileName;
            }
        }

        if ($_POST['action'] === 'create') {
            // Create new menu item (default is_active = 1)
           $data = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'price' => $price,
                'category_id' => $category_id,
                'is_drink' => $is_drink,
                'image_path' => $image_path
            ];
            $db->insert('menu_items', $data);
        } elseif ($_POST['action'] === 'update') {
            // Update existing menu item
            $id = $_POST['id'] ?? 0;
            // Get the current is_drink status
            $current_item = $db->fetch("SELECT is_drink FROM menu_items WHERE id = ?", [$id]);
            $was_drink = $current_item['is_drink'];

            $data = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'price' => $price,
                'category_id' => $category_id,
                'is_drink' => $is_drink,
                'is_active' => $is_active
            ];
            if ($image_path) {
                $data['image_path'] = $image_path;
            }
            $db->update('menu_items', $data, 'id = ?', [$id]);

            // If item was converted to a drink, create drink_stock entry
            if (!$was_drink && $is_drink) {
                $db->insert('drink_stock', [
                    'menu_item_id' => $id,
                    'quantity' => 0
                ]);
            }
        } elseif ($_POST['action'] === 'delete') {
            // Delete menu item
            $id = $_POST['id'] ?? 0;
            $db->delete('menu_items', 'id = ?', [$id]);
        }
        
        header('Location: menu.php');
        exit;
    }
}

// Get all categories with their parent info
$categories = $db->fetchAll("
    SELECT c.*, p.name as parent_name 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY CASE WHEN c.parent_id IS NULL THEN 0 ELSE 1 END, c.name
");

// Get all menu items with category info
$menuItems = $db->fetchAll("
    SELECT m.*, c.name as category_name, p.name as parent_category_name
    FROM menu_items m
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN categories p ON c.parent_id = p.id
    ORDER BY c.name, m.name
");

include 'header.php';
?>

<div class="container-fluid py-4">
    <!-- Add Menu Item Modal -->
    <div class="modal fade" id="addMenuItemModal" tabindex="-1" aria-labelledby="addMenuItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMenuItemModalLabel">Add New Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="menu.php" method="POST" enctype="multipart/form-data">
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
                                    <label for="price" class="form-label">Price</label>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <?php if ($category['parent_id'] === null): ?>
                                                <optgroup label="<?= h($category['name']) ?>">
                                                    <option value="<?= $category['id'] ?>"><?= h($category['name']) ?> (Main Category)</option>
                                                    <?php foreach ($categories as $subcat): ?>
                                                        <?php if ($subcat['parent_id'] === $category['id']): ?>
                                                            <option value="<?= $subcat['id'] ?>"><?= h($subcat['name']) ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="image" class="form-label">Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_drink" name="is_drink">
                                <label class="form-check-label" for="is_drink">
                                    Is Drink (Will create inventory entry)
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Menu Item</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Button -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuItemModal">
        Add Menu Item
    </button>

    <!-- Menu Items Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Menu Items</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="menuItemsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menuItems as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['image_path']): ?>
                                        <img src="<?= BASE_URL ?>/<?= h($item['image_path']) ?>" 
                                             alt="<?= h($item['name']) ?>" 
                                             class="menu-thumbnail"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </td>
                                <td><?= h($item['name']) ?></td>
                                <td>
                                    <?php if ($item['parent_category_name']): ?>
                                        <?= h($item['parent_category_name']) ?> &gt; 
                                    <?php endif; ?>
                                    <?= h($item['category_name']) ?>
                                </td>
                                <td><?= h($item['price']) ?> <?= CURRENCY ?></td>
                                <td><?= h($item['description']) ?></td>
                                <td>
                                    <?php if ($item['is_drink']): ?>
                                        <span class="badge bg-info">Drink</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Food</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-primary edit-item" 
                                                data-id="<?= $item['id'] ?>"
                                                data-name="<?= h($item['name']) ?>"
                                                data-price="<?= h($item['price']) ?>"
                                                data-description="<?= h($item['description']) ?>"
                                                data-category-id="<?= $item['category_id'] ?>"
                                                data-is-drink="<?= $item['is_drink'] ?>"
                                                data-is-active="<?= $item['is_active'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!--<button type="button" class="btn btn-danger delete-item"-->
                                        <!--        data-id="<?= $item['id'] ?>"-->
                                        <!--        data-name="<?= h($item['name']) ?>">-->
                                        <!--    <i class="fas fa-trash"></i>-->
                                        <!--</button>-->
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

<!-- Edit Menu Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="menu.php" method="POST" enctype="multipart/form-data" id="editForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_price" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="edit_price" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category_id" class="form-label">Category</label>
                        <select class="form-select" id="edit_category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <?php if ($category['parent_id'] === null): ?>
                                    <optgroup label="<?= h($category['name']) ?>">
                                        <option value="<?= $category['id'] ?>"><?= h($category['name']) ?> (Main Category)</option>
                                        <?php foreach ($categories as $subcat): ?>
                                            <?php if ($subcat['parent_id'] === $category['id']): ?>
                                                <option value="<?= $subcat['id'] ?>"><?= h($subcat['name']) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image" class="form-label">Image (Leave empty to keep current)</label>
                        <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_drink" name="is_drink">
                                    <label class="form-check-label" for="edit_is_drink">
                                        Is Drink
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                    <label class="form-check-label" for="edit_is_active">
                                        Is Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Menu Item</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Menu Item Modal -->
<div class="modal fade" id="deleteItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="deleteItemName"></span>"?</p>
                <form action="menu.php" method="POST" id="deleteForm">
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
    // Initialize menu items DataTable
    $('#menuItemsTable').DataTable({
        pageLength: 1000,
        order: [[1, 'asc']],  // Sort by name by default
        columnDefs: [
            { orderable: false, targets: [0, -1] }  // Disable sorting for image and actions columns
        ]
    });

    // Handle is_drink checkbox changes
    const editIsDrinkCheckbox = document.getElementById('edit_is_drink');
    editIsDrinkCheckbox.addEventListener('change', function() {
        const originalValue = this.getAttribute('data-original-value') === '1';
        const newValue = this.checked;

        if (originalValue !== newValue) {
            let message = originalValue ? 
                'Warning: Converting a drink item to a non-drink item may affect existing inventory records.' :
                'Warning: Converting a non-drink item to a drink will create a new inventory record with default stock of 0.';
                
            if (!confirm(message + '\n\nDo you want to continue?')) {
                this.checked = originalValue;
            }
        }
    });

    // Edit Menu Item
    const editButtons = document.querySelectorAll('.edit-item');
    const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const data = this.dataset;
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_price').value = data.price;
            document.getElementById('edit_category_id').value = data.categoryId;
            document.getElementById('edit_description').value = data.description;
            
            // Set is_drink checkbox
            const isDrink = data.isDrink === '1';
            const editIsDrinkCheckbox = document.getElementById('edit_is_drink');
            editIsDrinkCheckbox.checked = isDrink;
            editIsDrinkCheckbox.setAttribute('data-original-value', isDrink ? '1' : '0');
            
            // Set is_active checkbox
            const isActive = data.isActive === '1';
            document.getElementById('edit_is_active').checked = isActive;
            
            editModal.show();
        });
    });

    // Delete Menu Item
    const deleteButtons = document.querySelectorAll('.delete-item');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteItemModal'));
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const data = this.dataset;
            document.getElementById('delete_id').value = data.id;
            document.getElementById('deleteItemName').textContent = data.name;
            deleteModal.show();
        });
    });
});
</script>

<?php include 'footer.php'; ?>