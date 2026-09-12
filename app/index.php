<?php
require_once __DIR__ . '/RedisClient.php';

// Every visitor gets a session id via cookie - this is our cart key in Redis.
if (!isset($_COOKIE['shubham_sid'])) {
    $sid = bin2hex(random_bytes(8));
    setcookie('shubham_sid', $sid, time() + 3600, '/');
    $_COOKIE['shubham_sid'] = $sid;
}
$sid = $_COOKIE['shubham_sid'];

// Product catalog is read from the PVC-mounted "shared storage" path.
// This works both locally and in K8s (where __DIR__ is /var/www/html).
$dataFile = __DIR__ . '/data/products.json';
$products = [];
$dataError = null;

if (file_exists($dataFile)) {
    $products = json_decode(file_get_contents($dataFile), true) ?: [];
} else {
    $dataError = "products.json not found at $dataFile - ensure seed data is present.";
}

// Cart count - best-effort; if Redis is briefly unreachable we still
// render the shop, just without a live cart count.
$cartCount = null;
$redisError = null;
try {
    $r = new MiniRedis(getenv('REDIS_HOST') ?: 'redis', (int) (getenv('REDIS_PORT') ?: 6379));
    $items = $r->lrange("cart:$sid", 0, -1);
    $cartCount = is_array($items) ? count($items) : 0;
    $r->close();
} catch (Exception $e) {
    $redisError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ShubhamStore | Cloud-Native Commerce</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <a href="index.php" class="brand">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary)"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2v0a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12v0a2 2 0 0 1-2-2V7"/></svg>
      <h1>ShubhamStore</h1>
    </a>
    <a href="cart.php" class="cart-btn">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
      Cart<?= $cartCount !== null ? " <span style='background: white; color: var(--primary); padding: 2px 6px; border-radius: 99px; margin-left: 4px; font-size: 12px;'>$cartCount</span>" : '' ?>
    </a>
  </header>
  
  <main>
    <?php if ($redisError): ?>
      <div class="banner warn">
        <strong>Warning:</strong> Cache service unavailable (<?= htmlspecialchars($redisError) ?>). Browsing functionality remains intact.
      </div>
    <?php endif; ?>

    <?php if ($dataError): ?>
      <div class="banner warn"><?= htmlspecialchars($dataError) ?></div>
    <?php else: ?>
      
      <div class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Search products by name or description..." onkeyup="filterProducts()">
      </div>

      <div class="grid" id="productGrid">
        <?php foreach ($products as $p): ?>
          <div class="card product-card">
            <div class="emoji"><?= $p['emoji'] ?></div>
            <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
            <p class="desc product-desc"><?= htmlspecialchars($p['desc']) ?></p>
            <div class="price-row">
              <p class="price">$<?= number_format($p['price'] / 100, 2) ?></p>
              <form method="post" action="cart.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= htmlspecialchars($p['id']) ?>">
                <button type="submit">Add to Cart</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
  
  <footer>
    <div class="footer-content">
      <p>ShubhamStore Platform &copy; 2026 - Developed by Shubham Gavhane</p>
      <span class="footer-pod-info">Pod: <?= gethostname() ?></span>
    </div>
  </footer>

  <script>
    function filterProducts() {
      const query = document.getElementById('searchInput').value.toLowerCase();
      const cards = document.querySelectorAll('.product-card');
      
      cards.forEach(card => {
        const name = card.querySelector('.product-name').innerText.toLowerCase();
        const desc = card.querySelector('.product-desc').innerText.toLowerCase();
        
        if (name.includes(query) || desc.includes(query)) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>
