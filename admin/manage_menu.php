<?php
// admin/manage_menu.php
$pageClass = "admindash-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require __DIR__ . '/../includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success = "Menu item added.";
    } elseif ($_GET['success'] === 'updated') {
        $success = "Menu item updated.";
    } elseif ($_GET['success'] === 'deleted') {
        $success = "Menu item deleted.";
    }
}

$editItem = null;

// Handle delete
if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];

    // get image to delete
    $stmt = $pdo->prepare("SELECT image FROM menu_items WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    $item = $stmt->fetch();

    if ($item && $item['image']) {
        $imagePath = __DIR__ . '/../assets/images/' . $item['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id=:id");
    $stmt->execute([':id'=>$id]);

    header("Location: manage_menu.php?success=deleted");
    exit;
}

// Handle edit fetch
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $editItem = $stmt->fetch();
}

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? 'Main Course');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $imageName = '';

    // Validation
    if ($name === '') $errors[] = 'Name is required.';
    if ($price <= 0) $errors[] = 'Price must be greater than zero.';
    if ($quantity < 0) $errors[] = 'Quantity cannot be negative.';
    if (!in_array($category, ['Main Course','Fast Food','Salads','Dessert','Drink'])) {
        $errors[] = 'Invalid category.';
    }

    // Handle image upload (optional)
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/../assets/images/';
        $basename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $_FILES['image']['name']);
        $target = $uploadDir . $basename;
        $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Only JPG, JPEG, PNG, GIF images are allowed.';
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image size must be under 2MB.';
        } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $errors[] = 'Failed to upload image.';
        } else {
            $imageName = $basename;
        }
    }

    if (empty($errors)) {
        if ($id > 0) {



    if ($imageName && $editItem && $editItem['image']) {
        $oldImage = __DIR__ . '/../assets/images/' . $editItem['image'];
        if (file_exists($oldImage)) {
            unlink($oldImage);
        }
    }

    if ($imageName) {
        $sql = "UPDATE menu_items 
                SET name=:n, description=:d, price=:p, image=:i, category=:c, quantity=:q 
                WHERE id=:id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':n' => $name,
            ':d' => $description,
            ':p' => $price,
            ':i' => $imageName,
            ':c' => $category,
            ':q' => $quantity,
            ':id' => $id
        ]);

    } else {

        $sql = "UPDATE menu_items 
                SET name=:n, description=:d, price=:p, category=:c, quantity=:q 
                WHERE id=:id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':n' => $name,
            ':d' => $description,
            ':p' => $price,
            ':c' => $category,
            ':q' => $quantity,
            ':id' => $id
        ]);
    }
    header("Location: manage_menu.php?success=updated");
    exit;

    } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, image, category, quantity) VALUES (:n,:d,:p,:i,:c,:q)");
            $stmt->execute([
                ':n' => $name,
                ':d' => $description,
                ':p' => $price,
                ':i' => $imageName,
                ':c' => $category,
                ':q' => $quantity
            ]);
            header("Location: manage_menu.php?success=added");
            exit;
        }
        $editItem = null;
    }
}

// Fetch all menu items
$stmt = $pdo->query("SELECT * FROM menu_items ORDER BY category, created_at DESC");
$items = $stmt->fetchAll();
?>

<h1>Manage Menu</h1>

<?php if ($success): ?>
<div class="alert alert-success">
    <p><?php echo htmlspecialchars($success); ?></p>
</div>
<?php endif; ?>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <?php foreach ($errors as $e): ?>
    <p><?php echo htmlspecialchars($e); ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="manage-menu-wrapper">
    <h2><?php echo $editItem ? 'Edit Menu Item' : 'Add New Menu Item'; ?></h2>
    <form action="" method="post" enctype="multipart/form-data" class="form-card">
        <?php csrf_input(); ?>
        <input type="hidden" name="id" value="<?php echo $editItem ? (int)$editItem['id'] : 0; ?>">

        <div class="form-group">
            <label for="name">Name</label>
            <input required type="text" id="name" name="name"
                value="<?php echo $editItem ? htmlspecialchars($editItem['name']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description"
                name="description"><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label for="price">Price (Rs)</label>
            <input required type="number" step="0.01" id="price" name="price"
                value="<?php echo $editItem ? htmlspecialchars($editItem['price']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="quantity">Total Quantity</label>
            <input required type="number" min="0" id="quantity" name="quantity"
                value="<?php echo $editItem ? htmlspecialchars($editItem['quantity']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select name="category" id="category" required>
                <?php
                $cats = ['Main Course','Fast Food','Salads','Dessert','Drink'];
                foreach ($cats as $cat):
                    $selected = $editItem && $editItem['category']==$cat ? 'selected' : '';
                ?>
                <option value="<?php echo $cat; ?>" <?php echo $selected; ?>><?php echo $cat; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="image">Image</label>
            <input type="file" id="image" name="image" accept="image/*">
            <?php if ($editItem && $editItem['image']): ?>
            <p>Current Image:</p>
            <img src="../assets/images/<?php echo htmlspecialchars($editItem['image']); ?>" alt="Current Image"
                style="width:80px;height:80px;object-fit:cover;border-radius:6px;margin-top:8px;">
            <?php endif; ?>
        </div>

        <div class="admin-menu-form">
            <button type="submit" class="btn"><?php echo $editItem ? 'UPDATE' : 'ADD'; ?></button>
        </div>
    </form>
</div>

<h2>Existing Menu Items</h2>
<div class="admin-menu-grid">
    <?php foreach ($items as $item): ?>
    <div class="admin-menu-card">
        <?php if ($item['image']): ?>
        <img src="../assets/images/<?php echo htmlspecialchars($item['image']); ?>"
            alt="<?php echo htmlspecialchars($item['name']); ?>">
        <?php else: ?>
        <div
            style="width:100%;height:150px;background:#333;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#ffe3be;">
            No Image</div>
        <?php endif; ?>
        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
        <p>Price : Rs <?php echo number_format($item['price'], 2); ?></p>
        <p>Quantity : <?php echo htmlspecialchars($item['quantity']); ?></p>
        <p>Category : <?php echo htmlspecialchars($item['category']); ?></p>
        <form method="POST" action="" class="delete-form">
            <?php csrf_input(); ?>
            <a href="?edit=<?php echo (int)$item['id']; ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a>
            <input type="hidden" name="delete_id" value="<?php echo (int)$item['id']; ?>">
            <button type="submit" class="delete-btn" onclick="return confirm('Delete this item?');"><i class="fas fa-trash"></i> Delete</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<?php
require __DIR__ . '/../includes/footer.php';
?>