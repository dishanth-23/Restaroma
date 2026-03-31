<?php
// user/menu.php
$pageClass = "menu-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/header.php';

// Fetch all menu items
$stmt = $pdo->query("SELECT id, name, description, price, image, category 
FROM menu_items 
ORDER BY category, created_at DESC");
$items = $stmt->fetchAll();
?>

<!-- MENU HEADING -->
<section class="menu-header">
    <h1>Our Menu</h1>
    <div class="menu-divider"></div>
    <p>Crafted with passion. Served with elegance.</p>
</section>

<!-- MENU Filters -->
<div class="menu-filters">
    <button data-filter="all">All</button>
    <button data-filter="Main Course">Main Course</button>
    <button data-filter="Fast Food">Fast Food</button>
    <button data-filter="Salads">Salads</button>
    <button data-filter="Dessert">Dessert</button>
    <button data-filter="Drink">Drink</button>
</div>

<!-- MENU GRID -->
<section class="menu-grid">
    <?php foreach ($items as $item): ?>
    <div class="menu-card" data-category="<?php echo htmlspecialchars($item['category']); ?>">
        <?php if (!empty($item['image'])): ?>
        <img src="/Restaurant-System/assets/images/<?php echo htmlspecialchars($item['image']); ?>"
            alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
        <?php else: ?>
        <div class="menu-placeholder">No Image</div>
        <?php endif; ?>
        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
        <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
        <p class="price">Rs <?php echo number_format($item['price'], 2); ?></p>
        <a href="order.php" class="btn-primary">Order Now</a>
    </div>
    <?php endforeach; ?>
</section>

<?php
require __DIR__ . '/../includes/footer.php';
?>