<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'] ?? 0;
$search = $_GET['search'] ?? '';
$message = '';

// --- Handle Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Create Item
    if (isset($_POST['create_item'])) {
        $name = $_POST['item_name'];
        $cat = $_POST['category'];
        $price = $_POST['price'];
        
        $sql = "INSERT INTO inventory (owner_id, item_name, category, price) VALUES (:uid, :name, :cat, :price)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $user_id, ':name' => $name, ':cat' => $cat, ':price' => $price]);
        $message = "Item added successfully!";
    }
    
    // 2. Update Item
    if (isset($_POST['update_item'])) {
        $id = $_POST['item_id'];
        $name = $_POST['item_name'];
        $cat = $_POST['category'];
        $price = $_POST['price'];

        // Check ownership before update to prevent IDOR
        $stmt_check = $pdo->prepare("SELECT owner_id FROM inventory WHERE id = :id");
        $stmt_check->execute([':id' => $id]);
        $target_item = $stmt_check->fetch();

        // SECURITY FIX: Verify ownership before allowing update
        if ($target_item && $target_item['owner_id'] == $user_id) {
            // Update only own items
            $sql = "UPDATE inventory SET item_name = :name, category = :cat, price = :price WHERE id = :id AND owner_id = :uid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':name' => $name, ':cat' => $cat, ':price' => $price, ':id' => $id, ':uid' => $user_id]);
            $message = "Item updated successfully!";
        } else {
            $message = "Error: You cannot modify items that don't belong to you!";
        }
    }
    // 3. Delete Item
    if (isset($_POST['delete_item'])) {
        $id = $_POST['item_id'];
        $sql = "DELETE FROM inventory WHERE id = :id AND owner_id = :uid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id, ':uid' => $user_id]);
        $message = "Item deleted successfully!";
    }
}

// --- Fetch Inventory ---
$sql_inventory = "SELECT * FROM inventory WHERE owner_id = :owner_id";
$params_inv = [':owner_id' => $user_id];

if (!empty($search)) {
    $sql_inventory .= " AND item_name LIKE :search";
    $params_inv[':search'] = "%$search%";
}

$stmt_inv = $pdo->prepare($sql_inventory);
$stmt_inv->execute($params_inv);
$my_items = $stmt_inv->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Dashboard</title>
    <style>
        body { font-family: sans-serif; padding: 2rem; background-color: #f4f7f6; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 1rem; }
        .logout { color: red; text-decoration: none; }
        
        .section-title { border-bottom: 2px solid #007bff; padding-bottom: 5px; margin-top: 1rem; margin-bottom: 1rem; color: #333; }

        .search-box { margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .search-form { display: flex; gap: 10px; }
        .search-input { flex-grow: 1; padding: 12px 15px; font-size: 1.1rem; border: 1px solid #ced4da; border-radius: 5px; outline: none; transition: border-color 0.2s; }
        .search-input:focus { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
        .search-btn { padding: 12px 25px; font-size: 1.1rem; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.2s; font-weight: 500; }
        .search-btn:hover { background-color: #0056b3; }

        .action-bar { display: flex; justify-content: flex-end; margin-bottom: 1rem; }
        .add-btn { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; }
        .add-btn:hover { background-color: #218838; }

        .inventory-list-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .inventory-list-item:last-child { border-bottom: none; }
        .item-details { flex-grow: 1; }
        .item-actions { display: flex; gap: 10px; }
        
        .edit-btn { padding: 5px 15px; background: #ffc107; color: #333; border: none; border-radius: 4px; cursor: pointer; }
        .delete-btn { padding: 5px 15px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 25px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; position: relative; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: black; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .modal-btn { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($username); ?>'s Store</h1>
            <a href="logout.php" class="logout">Logout</a>
        </div>

        <?php if ($message) echo "<p style='color:green; font-weight:bold;'>$message</p>"; ?>

        <!-- Search & Actions -->
        <h2 class="section-title">Manage Inventory</h2>
        
        <div class="search-box">
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn">Search</button>
            </form>
            <?php if (!empty($search)): ?>
                <p>Results for: <b><?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?></b></p>
            <?php endif; ?>
        </div>

        <div class="action-bar">
            <button class="add-btn" onclick="openCreateModal()">+ Add New Item</button>
        </div>

        <!-- Inventory List -->
        <?php if (count($my_items) > 0): ?>
            <div style="background: white; border: 1px solid #ddd; border-radius: 4px;">
                <?php foreach ($my_items as $item): ?>
                    <div class="inventory-list-item">
                        <div class="item-details">
                            <strong style="font-size: 1.1em;"><?php echo htmlspecialchars($item['item_name']); ?></strong>
                            <br>
                            <span style="color: #666;"><?php echo htmlspecialchars($item['category']); ?></span>
                            <br>
                            <span style="color: #28a745; font-weight: bold;">$<?php echo number_format($item['price'], 2); ?></span>
                        </div>
                        <div class="item-actions">
                            <button class="edit-btn" onclick="openUpdateModal(
                                <?php echo $item['id']; ?>, 
                                '<?php echo addslashes($item['item_name']); ?>', 
                                '<?php echo addslashes($item['category']); ?>', 
                                <?php echo $item['price']; ?>
                            )">Edit</button>
                            
                            <form method="POST" onsubmit="return confirm('Are you sure?');" style="display:inline;">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="delete_item" value="1">
                                <button type="submit" class="delete-btn">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No inventory items found.</p>
        <?php endif; ?>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCreateModal()">&times;</span>
            <h3>Add New Item</h3>
            <form method="POST">
                <input type="hidden" name="create_item" value="1">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="item_name" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" required>
                </div>
                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                <button type="submit" class="modal-btn">Add Item</button>
            </form>
        </div>
    </div>

    <!-- Update Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeUpdateModal()">&times;</span>
            <h3>Edit Item</h3>
            <form method="POST">
                <input type="hidden" name="update_item" value="1">
                <input type="hidden" name="item_id" id="upd_id">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="item_name" id="upd_name" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" id="upd_cat" required>
                </div>
                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" step="0.01" name="price" id="upd_price" required>
                </div>
                <button type="submit" class="modal-btn">Update Item</button>
            </form>
        </div>
    </div>

    <script>
        // Create Modal
        var cModal = document.getElementById("createModal");
        function openCreateModal() { cModal.style.display = "block"; }
        function closeCreateModal() { cModal.style.display = "none"; }

        // Update Modal
        var uModal = document.getElementById("updateModal");
        function openUpdateModal(id, name, cat, price) {
            document.getElementById('upd_id').value = id;
            document.getElementById('upd_name').value = name;
            document.getElementById('upd_cat').value = cat;
            document.getElementById('upd_price').value = price;
            uModal.style.display = "block";
        }
        function closeUpdateModal() { uModal.style.display = "none"; }

        // Close outside click
        window.onclick = function(event) {
            if (event.target == cModal) cModal.style.display = "none";
            if (event.target == uModal) uModal.style.display = "none";
        }
    </script>
</body>
</html>
