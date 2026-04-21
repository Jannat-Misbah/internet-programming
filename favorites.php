<?php
require_once 'functions.php';
if(!is_logged_in()){
    $msg = urlencode('يجب تسجيل الدخول لعرض المفضلة.');
    redirect("login.php?msg={$msg}");
}
$user_id = current_user_id();

$stmt = $mysqli->prepare("SELECT p.* FROM favorites f JOIN products p ON f.product_id=p.id WHERE f.user_id=?");
$stmt->bind_param('i',$user_id);
$stmt->execute();
$res = $stmt->get_result();
$items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html><html lang="ar"><head><meta charset="utf-8"><title>المفضلة</title>
<link rel="stylesheet" href="styles.css"></head><body>
<div class="container">
  <h2>قائمة المفضلة</h2>
  <?php if(!$items): ?><p>لا توجد عناصر مفضلة.</p><?php else: ?>
    <div class="products">
      <?php foreach($items as $p): ?>
        <div class="card">
          <img src="images/<?= htmlspecialchars($p['image']?:'no-image.png') ?>" alt="">
          <div class="title"><?= htmlspecialchars($p['name_ar']) ?></div>
          <div class="price"><?= $p['price'] ?> LD</div>
          <div class="actions">
            <button class="btn add-to-cart" data-id="<?= $p['id'] ?>">اضف للسلة</button>
            <button class="btn btn-muted add-to-fav" data-id="<?= $p['id'] ?>">إزالة</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="scripts.js"></script>
</body></html>