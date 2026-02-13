<?php
require_once 'db_connect.php';
session_start();

// Ensure user is logged in - DISABLED for public access
// if (!isset($_SESSION['username'])) {
//     header("Location: index.php");
//     exit;
// }
//SELECT * FROM inventory WHERE item_name LIKE a ORDER BY item_name;
$search = $_GET['search'] ?? '';

$sort = $_GET['sort'] ?? 'item_name'; // Default sort

// SECURITY FIX: Whitelist allowed sort columns to prevent SQL injection
$allowed_sorts = ['item_name', 'price', 'quantity', 'category'];
if (!in_array($sort, $allowed_sorts, true)) {
    $sort = 'item_name'; // Default to safe column
}

try {
    // secure search using prepared statements
    //$sql = "SELECT * FROM inventory WHERE item_name LIKE :search";
    $sql = "SELECT inventory.*, users.username as store_name 
            FROM inventory 
            JOIN users ON inventory.owner_id = users.id 
            WHERE item_name LIKE :search";
    
    // SECURITY FIX: Use whitelist for ORDER BY to prevent SQL injection
    $sql .= " ORDER BY " . $sort;
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':search', "%$search%");
    $stmt->execute();
    $items = $stmt->fetchAll();

} catch (PDOException $e) {
    // In a Blind SQLi scenario, errors might be suppressed or generic.
    // For educational purposes, we show it, but a real blind attack works even if this is hidden.
    // SECURITY FIX: Do not expose database errors to users
    // $error = "Database Error: " . $e->getMessage();
    error_log("Database Error: " . $e->getMessage()); // Log to file instead
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Search</title>
    <style>
        body { font-family: sans-serif; padding: 2rem; background-color: #f4f7f6; }
        .container { background: white; padding: 2rem; border-radius: 8px; max-width: 800px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 1rem; }
        .controls { margin-bottom: 2rem; padding: 1rem; background: #eee; border-radius: 4px; display: flex; gap: 10px; align-items: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #007bff; color: white; }
        .back-link { display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="welcome.php" class="back-link">&larr; Back to Dashboard</a>
        <h1>Inventory System</h1>
        
        <form class="controls" method="GET">
            <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
            
            <label for="sort">Sort By:</label>
            <select name="sort" onchange="this.form.submit()">
                <option value="item_name" <?php echo $sort === 'item_name' ? 'selected' : ''; ?>>Name</option>
                <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Price</option>
                <option value="quantity" <?php echo $sort === 'quantity' ? 'selected' : ''; ?>>Quantity</option>
            </select>
            
            <button type="submit">Filter</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Store</th>
                    <th>Price</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <tr data-id="<?php echo htmlspecialchars($item['id']); ?>">
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No items found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
