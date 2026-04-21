<?php
require_once 'functions.php';
require_once 'validation_functions.php';

if(!is_logged_in()) {
    redirect_with_msg('login.php', 'يجب تسجيل الدخول أولاً للوصول إلى السلة');
}
$user = current_user();
$user_id = $user['id'];

// جلب محتويات السلة مع بيانات المنتج
$stmt = $mysqli->prepare("SELECT ci.id as row_id, ci.quantity, p.id as product_id, p.name_ar, p.price, p.stock, p.image FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
//حساب الاجمالي و التحقق من المخزون
$total = 0;
$stock_issue = false; // علامة  اذا هناك مشكلة في المحزون
foreach($items as $it){
    $total += $it['quantity'] * $it['price'];
    if($it['quantity'] > $it['stock']) $stock_issue = true; // لو الكمية اكبر من المخزون
}
?>
<!doctype html>
<html lang="ar">
<head><meta charset="utf-8"><title>السلة</title>
<link rel="stylesheet" href="styles.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container">
  <h2>سلة التسوق</h2>

  <?php if(!$items): ?>
    <p>السلة فارغة.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>المنتج</th>
          <th>الكمية</th>
          <th>سعر الوحدة</th>
          <th>المجموع</th>
          <th>المخزون متاح</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($items as $it): ?>
          <tr id="cart-row-<?= $it['row_id'] ?>"> 
            <td style="min-width:200px">
              <img src="images/<?= htmlspecialchars($it['image']?:'no-image.png') ?>" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:6px;margin-left:8px;vertical-align:middle">
              <span><?= htmlspecialchars($it['name_ar']) ?></span>
            </td>
            <td>
              <input type="number" min="1" class="cart-qty" data-id="<?= $it['row_id'] ?>" value="<?= $it['quantity'] ?>">
            </td>
            <td><?= $it['price'] ?> LD</td>
            <td><?= $it['price'] * $it['quantity'] ?> LD</td>
            <td>
              <?php if($it['stock'] <= 0): ?>
                <span style="color:#b00020">عذرًا، غير متوفر</span>
              <?php else: ?>
                <span>متوفر (<?= $it['stock'] ?>)</span>
              <?php endif; ?>
            </td>
            <td>
              <button class="btn btn-danger delete-cart-item" data-id="<?= $it['row_id'] ?>">حذف</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <p>الإجمالي: <strong id="cartTotal"><?= $total ?>LD</strong></p>

    <?php if($stock_issue): ?>
      <div style="background:#fff3cd;padding:10px;border-radius:6px;margin-bottom:8px;color:#856404">
        أحد العناصر في سلتك يتجاوز المخزون المتاح. الرجاء تعديل الكميات قبل إنهاء الشراء.
      </div>
    <?php endif; ?>

    <form id="checkoutForm" method="post" action="generate_invoice.php">
      <input type="hidden" name="action" value="checkout">
      <!-- نموذج بيانات الشحن    -->

    <h3>بيانات الشحن والدفع</h3>
       <form id="checkoutForm" method="post" action="generate_invoice.php">
      <input type="hidden" name="action" value="checkout">
      <label>الاسم</label>
      <input class="input-transparent" name="name" required value="<?= htmlspecialchars($user['username'] ?? '') ?>">

      <label>البريد الإلكتروني</label>
      <input class="input-transparent" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">

      <label>رقم الهاتف الأول</label>
      <input class="input-transparent" name="phone" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>">

      <label>رقم الهاتف الاحتياطي (اختياري)</label>
      <input class="input-transparent" name="phone_alt" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">

      <label>العنوان</label>
      <textarea class="input-transparent" name="address" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>

      <label>ملاحظات (اختياري)</label>
      <textarea class="input-transparent" name="notes"></textarea>

      <p style="margin-top:8px"><em>طرق الدفع: عند الاستلام</em></p>
      <div style="margin-top:12px"></div>
      <button class="btn" type="submit" <?= $stock_issue ? 'disabled title="قم بتحديث الكميات أولاً"' : '' ?>>إتمام الشراء وإنشاء الفاتورة</button>      
       </div>
    </form>
  <?php endif; ?>
</div>

<script src="scripts.js"></script>
</body>
</html>