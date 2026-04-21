<?php
require_once 'functions.php';

// AJAX تحميل المنتجات
if(isset($_GET['ajax']) && $_GET['ajax']==1){
    $q = trim($_GET['q'] ?? '');//البحث النصي
    $cat = trim($_GET['category'] ?? ''); //يعرف الفئة المحددة....يفحص لو موجود في $q و لو مافيش ياخد null

    $sql = "SELECT p.*, c.name_ar as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE 1=1 ";//لاسترجاع المنتجات مع اسماء فئاتها
    if($q !== ''){//لوفي نص يحث
        $q_esc = $mysqli->real_escape_string($q);//
        $sql .= " AND (p.name_ar LIKE '%{$q_esc}%' OR p.description_ar LIKE '%{$q_esc}%') ";//بحث في الاسم او الوصف
    }
    if($cat !== ''){
        $cat_id = intval($cat);//تحويل لعدد صحيح لمنع الهجمات
        $sql .= " AND p.category_id = {$cat_id} ";
    }
    $sql .= " ORDER BY p.created_at DESC LIMIT 200"; //ترتيب حسب تاريخ الانشاء

    $res = $mysqli->query($sql);
    if(!$res || $res->num_rows === 0){
        echo '<p>لا توجد منتجات مطابقة.</p>'; exit; 
    }
    while($p = $res->fetch_assoc()){//
        echo '<div class="card">';
        echo '<a href="product_details.php?id='.$p['id'].'"><img src="images/' . ($p['image']?:'no-image.png') . '" alt="" style="display:block;width:100%;height:150px;object-fit:cover"></a>';
        
        echo '<div class="title"><a href="product_details.php?id='.$p['id'].'">'.htmlspecialchars($p['name_ar']).'</a></div>';
        echo '<div class="price">'.$p['price'].' LD</div>';
        echo '<div class="actions">';
        echo '<button class="btn add-to-cart" data-id="'.$p['id'].'">اضف للسلة</button>';
        echo '<button class="btn btn-muted add-to-fav" data-id="'.$p['id'].'">المفضلة</button>';
        echo '</div></div>';
    }
    exit;
}

// جلب الفئات للقائمة الجانبية
$cats = [];
$res = $mysqli->query("SELECT * FROM categories ORDER BY id");
if($res){
    while($row = $res->fetch_assoc()) $cats[] = $row;
}

// منتجات عرض أولي (كلها تظهر 24 منتج اولي)
$products = [];
$res2 = $mysqli->query("SELECT p.*, c.name_ar as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.created_at DESC LIMIT 24");
if($res2){
    while($row = $res2->fetch_assoc()) $products[] = $row;
}
?>
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>متجر الأثاث الإلكتروني</title>
<link rel="stylesheet" href="styles.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// نمرّر حالة تسجيل الدخول للـ JS
const isLoggedIn = <?= is_logged_in() ? 'true' : 'false' ?>;
</script>
<script src="scripts.js"></script>
</head>
<body>
 <!--  الجزء المتعلق بالشريط العلوي  -->
  <div class="topbar">
    <div id="offersToggle" class="icon" title="قائمة العروض"><img src="images/icon_menu.png" alt="menu" class="top-icon"></div>
    <form id="searchForm" class="search-bar" method="get" onsubmit="return false;">
      <input type="text" id="q" name="q" placeholder="ابحث عن منتج...">
      <button class="btn" onclick="$('#searchForm').submit();">بحث</button>
    </form>

    <div class="icon-row" style="margin-left:auto;">
      <div class="icon requires-login" data-href="favorites.php" data-msg="يجب تسجيل الدخول لعرض المفضلة" title="المفضلة"><img src="images/icon_fav.png" alt="fav" class="top-icon"></div>
      <div class="icon requires-login" data-href="cart.php" data-msg="يجب تسجيل الدخول للوصول إلى السلة" title="السلة"><img src="images/icon_cart.png" alt="cart" class="top-icon"></div>
      <div class="icon requires-login" data-href="my_orders.php" data-msg="يجب تسجيل الدخول لعرض مشترياتي" id="ordersIcon" title="مشترياتي"><img src="images/icon_orders.png" alt="orders" class="top-icon"></div>
      <div class="icon" title="الملف الشخصي" onclick="location.href='login.php'"><img src="images/icon_profile.png" alt="profile" class="top-icon"></div>
    </div>
  </div>

  <!-- sidebar (مخفية افتراضيا) -->
  <div id="offersSidebar" class="sidebar" aria-hidden="true" style="display:none;">
    <div class="sidebar-header">
      <h3>قائمة العروض</h3>
      <button id="closeSidebar" class="btn">×</button>
    </div>
    <ul class="sidebar-list">
      <li class="sidebar-item" data-id="">الكل</li>
      <?php foreach($cats as $c): ?>
        <li class="sidebar-item" data-id="<?= $c['id'] ?>"><?= htmlspecialchars($c['name_ar']) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div id="sidebarOverlay" class="sidebar-overlay" tabindex="-1" style="display:none;"></div>

  <div class="container">
    <div id="productsWrap" class="products">
      <?php if($products): foreach($products as $p): ?>
      <?php
// داخل حلقة عرض المنتجات (مثال لـ while/foreach لكل $p)
?>
<?php
// داخل حلقة عرض المنتجات لكل $p
?>
<div class="card">
  <a href="product_details.php?id=<?= $p['id'] ?>">
    <img src="images/<?= htmlspecialchars($p['image']?:'no-image.png') ?>" alt="" style="width:100%;height:150px;object-fit:cover;border-radius:6px">
  </a>
  <div class="title"><a href="product_details.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name_ar']) ?></a></div>
  <div class="price"><?= $p['price'] ?> LD</div>
  <div class="stock-info">
    <?php if(intval($p['stock']) <= 0): ?>
      <span style="color:#b00020">عذرًا، المنتج غير متوفر</span>
    <?php else: ?>
      <span>المتاح: <?= intval($p['stock']) ?></span>
    <?php endif; ?>
  </div>
  <div class="actions">
    <?php if(intval($p['stock']) > 0): ?>
      <button class="btn add-to-cart" data-id="<?= $p['id'] ?>">اضف للسلة</button>
    <?php else: ?>
      <button class="btn btn-muted" disabled>غير متوفر</button>
    <?php endif; ?>
     <button class="btn btn-muted add-to-fav" data-id="<?= $p['id'] ?>">المفضلة</button>
    <button class="btn btn-muted" onclick="location.href='product_details.php?id=<?= $p['id'] ?>'">التفاصيل</button>
  </div>
</div>
<?php
// نهاية البطاقة
?>
<?php?>
      <?php endforeach; else: ?>
        <p>لا توجد منتجات حالياً.</p>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>