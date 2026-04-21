<?php
require_once 'functions.php';
if(!is_logged_in()){ redirect_with_msg('login.php', 'يجب تسجيل الدخول أولاً للوصول إلى هذه الصفحة'); }
$user = current_user(); 
$user_id = $user['id'] ?? null; 

//  جلب الطلبات السابقة من جدول الاوردرز  my_orders.php
$stmt = $mysqli->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param('i',$user_id); //
$stmt->execute();
$res = $stmt->get_result();
$orders = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html><html lang="ar"><head><meta charset="utf-8"><title>الملف الشخصي</title>
<link rel="stylesheet" href="styles.css"></head><body>
<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h2>حسابي - <?= htmlspecialchars($user['username'] ?? '') ?></h2>
    <div>
      <button class="btn" onclick="location.href='index.php'">العودة للرئيسية</button>
      <button class="btn btn-muted" onclick="location.href='logout.php'">تسجيل الخروج</button>
    </div>
  </div>

  <p>مرحبًا <?= htmlspecialchars($user['username'] ?? '') ?>. يمكنك عرض مشترياتك من صفحة <a href="my_orders.php">مشترياتي</a>.</p>

  <h3>الفواتير والطلبات</h3>
  <?php if(!$orders): ?><p>لا توجد مشتريات حتى الآن.</p><?php else: ?>
    <table class="table">
      <thead><tr><th>رقم الفاتورة</th><th>التاريخ</th><th>المبلغ</th><th>الحالة</th><th>تفاصيل</th></tr></thead>
      <tbody>
        <?php foreach($orders as $o): ?>
          <tr>
            <td><?= htmlspecialchars($o['invoice_number']) ?></td>
            <td><?= $o['created_at'] ?></td>
            <td><?= $o['total_amount'] ?> LD</td>
            <td><?= htmlspecialchars($o['status']) ?></td>
            <td><a class="btn" href="invoice_details.php?id=<?= $o['id'] ?>">عرض</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
</body></html>