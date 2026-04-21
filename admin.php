<?php
require_once 'functions.php';
if(!is_logged_in() || !is_admin()){
    echo "<p>هذه الصفحة محمية. <a href='login.php'>تسجيل الدخول كمدير</a></p>";
    exit;
}

// تبويب نشط (products أو orders)
$tab = $_GET['tab'] ?? 'products';

// إحصاءات
$total_products = 0;
$res = $mysqli->query("SELECT COUNT(*) as c FROM products");
if($res) { $total_products = $res->fetch_assoc()['c'] ?? 0; $res->close(); }

$total_users = 0;
$res2 = $mysqli->query("SELECT COUNT(*) as c FROM users WHERE role <> 'admin'");
if($res2) { $total_users = $res2->fetch_assoc()['c'] ?? 0; $res2->close(); }

// عدد الطلبات الإجمالي (جميع الطلبات)
$total_orders = 0;
$rOrders = $mysqli->query("SELECT COUNT(*) as c FROM orders");
if($rOrders){ $total_orders = $rOrders->fetch_assoc()['c'] ?? 0; $rOrders->close(); }

// إجمالي المبيعات
$total_sales = 0;
$r3 = $mysqli->query("SELECT IFNULL(SUM(total_amount),0) as s FROM orders");
if($r3){ $total_sales = $r3->fetch_assoc()['s'] ?? 0; $r3->close(); }

// جلب المنتجات والطلبات (تهيئة آمنة)
$products = [];
$orders = [];

if($tab === 'products'){
    $resp = $mysqli->query("SELECT p.*, c.name_ar as cat FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC");
    if($resp){ while($row = $resp->fetch_assoc()) $products[] = $row; $resp->close(); }
} else {
    $reso = $mysqli->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC");
    if($reso){ while($row = $reso->fetch_assoc()) $orders[] = $row; $reso->close(); }
}
?>
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8">
<title>لوحة التحكم - المدير</title>
<link rel="stylesheet" href="styles.css">
<style>
/* إحصاءات */
.stats-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.stat-card{flex:1;min-width:180px;background:linear-gradient(180deg,#fff,#fbf7f3);border-radius:10px;padding:12px;border:1px solid rgba(0,0,0,0.04)}
.stat-card h4{margin:0 0 6px 0;color:#6b4f3a}
.stat-card .value{font-size:20px;font-weight:bold;color:#c37f4f}
@media(max-width:700px){ .stat-card{min-width:140px} }
.admin-success{ background:#e6f7ea; border:1px solid #bfe6c6; color:#175a2c; padding:10px; border-radius:6px; margin-bottom:12px; }
.top-tabs{display:flex;gap:8px;margin-bottom:12px}
.top-tabs a{ text-decoration:none }
</style>
</head>
<body>
<div class="container">
  <h2>لوحة التحكم</h2>
  <p><a href="logout.php">خروج</a></p>

  <!-- بطاقات الإحصائيات -->
  <div class="stats-row">
    <div class="stat-card"><h4>عدد المنتجات</h4><div class="value"><?= $total_products ?></div></div>
    <div class="stat-card"><h4>عدد المستخدمين (باستثناء الإداريين)</h4><div class="value"><?= $total_users ?></div></div>
    <div class="stat-card"><h4>إجمالي الطلبات</h4><div class="value"><?= $total_orders ?></div></div>
    <div class="stat-card"><h4>إجمالي المبيعات</h4><div class="value"><?= $total_sales ?> LD</div></div>
  </div>

  <?php if(!empty($_SESSION['admin_success'])): ?>
    <div class="admin-success"><?= $_SESSION['admin_success'] ?></div>
    <?php unset($_SESSION['admin_success']); ?>
  <?php endif; ?>

  <!-- تبويبات -->
  <div class="top-tabs">
    <a class="btn <?= $tab==='products'?'':'btn-muted' ?>" href="admin.php?tab=products">المنتجات</a>
    <a class="btn <?= $tab==='orders'?'':'btn-muted' ?>" href="admin.php?tab=orders">الطلبات</a>
  </div>

  <!-- محتوى المنتجات -->
  <?php if($tab === 'products'): ?>
    <h3>المنتجات</h3>
    <p><a href="admin_actions.php?action=new" class="btn">إضافة منتج جديد</a></p>
    <table class="table">
      <thead><tr><th>اسم</th><th>فئة</th><th>السعر</th><th>المخزون</th><th>صورة</th><th>إجراءات</th></tr></thead>
      <tbody>
        <?php foreach($products as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['name_ar']) ?></td>
            <td><?= htmlspecialchars($p['cat']) ?></td>
            <td><?= $p['price'] ?></td>
            <td><?= $p['stock'] ?></td>
            <td><?= htmlspecialchars($p['image']) ?></td>
            <td>
              <a class="btn" href="admin_actions.php?action=edit&id=<?= $p['id'] ?>">تعديل</a>
              <a class="btn btn-muted" href="admin_actions.php?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('تأكيد الحذف؟')">حذف</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <!-- محتوى الطلبات -->
  <?php else: ?>
    <h3>الطلبات</h3>
    <table class="table">
      <thead><tr><th>فاتورة</th><th>مستخدم</th><th>المبلغ</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
        <?php foreach($orders as $o): ?>
          <tr>
            <td><?= htmlspecialchars($o['invoice_number']) ?></td>
            <td><?= htmlspecialchars($o['username']) ?></td>
            <td><?= $o['total_amount'] ?></td>
            <td><?= htmlspecialchars($o['status']) ?></td>
            <td>
              <a class="btn" href="admin_actions.php?action=view_order&id=<?= $o['id'] ?>">عرض</a>

              <!-- زر تغيير الحالة: يتحوّل تبعاً للحالة الحالية -->
              <?php if($o['status'] !== 'مكتمل'): ?>
                <a class="btn" href="admin_actions.php?action=change_status&id=<?= $o['id'] ?>&status=مكتمل">وضع مكتمل</a>
              <?php else: ?>
                <a class="btn" href="admin_actions.php?action=change_status&id=<?= $o['id'] ?>&status=قيد التجهيز">إرجاع إلى قيد التجهيز</a>
              <?php endif; ?>

              <a class="btn btn-muted" href="admin_actions.php?action=delete_order&id=<?= $o['id'] ?>" onclick="return confirm('هل تريد حذف الطلب؟')">حذف الطلب</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

</div>
</body>
</html>