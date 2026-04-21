<?php
require_once 'functions.php';
require_once 'validation_functions.php';

// حماية: يجب أن يكون المستخدم مسجلاً
if(!is_logged_in()){
    redirect_with_msg('login.php', 'يجب تسجيل الدخول أولاً للوصول إلى صفحة الفاتورة');
}

// جلب id وتنقيته
$order_id = sanitize_int($_GET['id'] ?? 0);
if($order_id <= 0){
    redirect_with_msg('my_orders.php', 'رقم فاتورة غير صالح');
}

// جلب الطلب مع اسم المستخدم
$stmt = $mysqli->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();

if(!$order){
    redirect_with_msg('my_orders.php', 'الطلب غير موجود أو لا يمكنك الوصول إليه');
}

// التحقق من الصلاحية: صاحب الطلب أو المشرف
$current = current_user();
if(!$current){
    redirect_with_msg('login.php', 'يجب تسجيل الدخول');
}
if($current['id'] != $order['user_id'] && !is_admin()){
    redirect_with_msg('my_orders.php', 'ليس لديك صلاحية لعرض هذه الفاتورة');
}

// جلب عناصر الطلب
$stmt2 = $mysqli->prepare("SELECT product_name_ar, unit_price, quantity, subtotal FROM order_items WHERE order_id = ?");
$stmt2->bind_param('i', $order_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$items = $res2->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// حساب المجموع احتياطياً إن لم يكن محفوظاً
$total_calc = 0;
foreach($items as $it) $total_calc += floatval($it['subtotal']);
?>
<!doctype html>
<html lang="ar">
<head>
  <meta charset="utf-8">
  <title>تفاصيل الفاتورة - <?= htmlspecialchars($order['invoice_number']) ?></title>
  <link rel="stylesheet" href="styles.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h2>تفاصيل الفاتورة</h2>
    <div>
      <button class="btn" onclick="location.href='index.php'">العودة للرئيسية</button>
      <button class="btn btn-muted" onclick="location.href='logout.php'">تسجيل الخروج</button>
    </div>
  </div>

  <h3>معلومات الفاتورة</h3>
  <p><strong>رقم الفاتورة:</strong> <?= htmlspecialchars($order['invoice_number']) ?></p>
  <p><strong>التاريخ:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
  <p><strong>الحالة:</strong> <?= htmlspecialchars($order['status']) ?></p>

  <h3>المنتجات</h3>
  <?php if(!$items): ?>
    <p>لا توجد عناصر في هذه الفاتورة.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>المنتج</th><th>الكمية</th><th>سعر الوحدة</th><th>المجموع الجزئي</th></tr></thead>
      <tbody>
        <?php foreach($items as $it): ?>
          <tr>
            <td><?= htmlspecialchars($it['product_name_ar']) ?></td>
            <td><?= intval($it['quantity']) ?></td>
            <td><?= htmlspecialchars($it['unit_price']) ?> LD</td>
            <td><?= htmlspecialchars($it['subtotal']) ?> LD</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p><strong>المجموع الكلي:</strong> <?= htmlspecialchars($order['total_amount'] ?? $total_calc) ?> LD</p>
  <?php endif; ?>

  <h3>بيانات الشحن</h3>
  <p><strong>الهاتف:</strong> <?= htmlspecialchars($order['phone']) ?></p>
  <p><strong>الهاتف الاحتياطي:</strong> <?= htmlspecialchars($order['phone_alt']) ?></p>
  <p><strong>العنوان:</strong> <?= nl2br(htmlspecialchars($order['address'])) ?></p>
  <p><strong>ملاحظات:</strong> <?= nl2br(htmlspecialchars($order['notes'])) ?></p>

</div>
  <p><strong>الحالة:</strong> <?= htmlspecialchars($order['status']) ?></p>
  <div style="margin-top:8px">
    <a class="btn" href="invoice_pdf.php?id=<?= $order['id'] ?>" target="_blank">تنزيل PDF</a>
  </div>
...
</body>
</html>