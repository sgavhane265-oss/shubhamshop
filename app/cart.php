<?php
require_once __DIR__ . '/RedisClient.php';

if (!isset($_COOKIE['shubham_sid'])) {
    header('Location: index.php');
    exit;
}
$sid = $_COOKIE['shubham_sid'];

$dataFile = __DIR__ . '/data/products.json';
$products = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?: []) : [];
$productsById = [];
foreach ($products as $p) {
    $productsById[$p['id']] = $p;
}

$redisError = null;
$cartItems = [];

try {
    $r = new MiniRedis(getenv('REDIS_HOST') ?: 'redis', (int) (getenv('REDIS_PORT') ?: 6379));

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
        $productId = $_POST['product_id'] ?? '';
        if (isset($productsById[$productId])) {
            $r->lpush("cart:$sid", $productId);
        }
        $r->close();
        header('Location: cart.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear') {
        $r->del("cart:$sid");
        $r->close();
        header('Location: cart.php');
        exit;
    }

    $ids = $r->lrange("cart:$sid", 0, -1);
    $r->close();

    $total = 0;
    foreach ((array) $ids as $id) {
        if (isset($productsById[$id])) {
            $cartItems[] = $productsById[$id];
            $total += $productsById[$id]['price'];
        }
    }
} catch (Exception $e) {
    $redisError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Cart | ShubhamStore</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <a href="index.php" class="brand">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary)"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2v0a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12v0a2 2 0 0 1-2-2V7"/></svg>
      <h1>ShubhamStore</h1>
    </a>
    <a href="index.php" class="cart-btn" style="background: var(--bg-color); color: var(--text-main); border: 1px solid var(--border-color);">
      &larr; Continue Shopping
    </a>
  </header>
  
  <main>
    <?php if ($redisError): ?>
      <div class="banner warn">
        <strong>Error:</strong> Cart service is currently unavailable. (<?= htmlspecialchars($redisError) ?>)<br><br>
        <em>System Note: This indicates a readiness probe failure in the cache layer.</em>
      </div>
    <?php else: ?>
      <div class="cart-container">
        <div class="cart-header">
          <h2>Shopping Cart</h2>
          <span style="color: var(--text-muted); font-weight: 500;"><?= count($cartItems) ?> Items</span>
        </div>

        <?php if (empty($cartItems)): ?>
          <div style="text-align: center; padding: 3rem 0; color: var(--text-muted);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
            <p>Your cart is currently empty.</p>
          </div>
        <?php else: ?>
          <?php foreach ($cartItems as $item): ?>
            <div class="cart-item">
              <div class="item-emoji"><?= $item['emoji'] ?></div>
              <div class="item-details">
                <div class="item-title"><?= htmlspecialchars($item['name']) ?></div>
                <div style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.25rem;">ShubhamStore Collection</div>
              </div>
              <div class="item-price">$<?= number_format($item['price'] / 100, 2) ?></div>
            </div>
          <?php endforeach; ?>
          
          <div class="cart-total">
            <span>Total</span>
            <span style="color: var(--primary);">$<?= number_format($total / 100, 2) ?></span>
          </div>
          
          <div class="cart-actions">
            <form method="post">
              <input type="hidden" name="action" value="clear">
              <button type="submit" class="btn-secondary">Clear Cart</button>
            </form>
            <button class="btn-primary" onclick="alert('Checkout integration pending in later roadmap phases.')">Proceed to Checkout</button>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>
  
  <footer>
    <div class="footer-content">
      <p>ShubhamStore Platform &copy; 2026 - Developed by Shubham Gavhane</p>
      <span class="footer-pod-info">Session ID: <?= htmlspecialchars($sid) ?> | Pod: <?= gethostname() ?></span>
    </div>
  </footer>
</body>
</html>
