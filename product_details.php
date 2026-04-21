<?php
require_once 'functions.php';
require_once 'validation_functions.php';

$id = sanitize_int($_GET['id'] ?? 0);
if($id <= 0) redirect_with_msg('index.php', 'منتج غير صالح.');

$stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();
if(!$product) redirect_with_msg('index.php','المنتج غير موجود.');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>تفاصيل المنتج - <?= htmlspecialchars($product['name_ar']) ?></title>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
.product-page{display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start;direction:rtl}
.prod-image{width:560px;height:560px;object-fit:cover;border-radius:8px;border:1px solid rgba(0,0,0,0.06)}
.prod-info{flex:1;min-width:320px;text-align:right}
.specs dt{font-weight:bold;margin-top:6px}
@media(max-width:900px){ .prod-image{width:100%;height:auto} .product-page{flex-direction:column} }
</style>
</head>
<body>
<div class="container">
  <h2 style="text-align:right">تفاصيل المنتج</h2>
  <div class="product-page">
    <div>
      <img src="images/<?= htmlspecialchars($product['image']?:'no-image.png') ?>" alt="" class="prod-image">
    </div>
    <div class="prod-info">
      <h3><?= htmlspecialchars($product['name_ar']) ?><?php if(!empty($product['name_en'])): ?> / <?= htmlspecialchars($product['name_en']) ?><?php endif; ?></h3>
      <p><?= nl2br(htmlspecialchars($product['description_ar'])) ?></p>

      <dl class="specs">
        <dt>اللون:</dt><dd><?= htmlspecialchars($product['color'] ?? '') ?></dd>
        <dt>الأبعاد:</dt><dd><?= htmlspecialchars($product['dimensions'] ?? '') ?></dd>
        <dt>المادة:</dt><dd><?= htmlspecialchars($product['material'] ?? '') ?></dd>
        <dt>الوزن:</dt><dd><?= htmlspecialchars($product['weight'] ?? '') ?></dd>
        <dt>الضمان:</dt><dd><?= htmlspecialchars($product['warranty'] ?? '') ?></dd>
      </dl>

      <p><strong>السعر:</strong> <?= htmlspecialchars($product['price']) ?> LD</p>
      <p><strong>المخزون:</strong> <?= htmlspecialchars($product['stock']) ?></p>

      <!-- ضمن صفحة product_details.php في مكان الزرّ -->
<?php if(intval($product['stock']) > 0): ?>
  <button class="btn add-to-cart" data-id="<?= $product['id'] ?>">اضف للسلة</button>
  <span style="margin-right:8px">المتاح: <?= intval($product['stock']) ?></span>
<?php else: ?>
  <button class="btn btn-muted" disabled>غير متوفر</button>
  <span style="margin-right:8px;color:#b00020">الكمية: 0</span>
<?php endif; ?>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="scripts.js"></script>
</body>
</html>