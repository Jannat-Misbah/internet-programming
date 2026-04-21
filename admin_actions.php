<?php
require_once 'functions.php';
require_once 'validation_functions.php';
if(!is_logged_in() || !is_admin()) { echo "ممنوع"; exit; }
$action = $_GET['action'] ?? '';

if($action === 'new' || $action === 'edit'){
    $id = intval($_GET['id'] ?? 0);
    $product = ['name_ar'=>'','description_ar'=>'','price'=>0,'stock'=>0,'category_id'=>'','image'=>'','color'=>'','dimensions'=>'','material'=>'','weight'=>'','warranty'=>''];
    if($action === 'edit' && $id>0){
        $stmt = $mysqli->prepare("SELECT * FROM products WHERE id=?");
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $res = $stmt->get_result();
        $product = $res->fetch_assoc() ?: $product;
        $stmt->close();
    }
    $cats = [];
    $res2 = $mysqli->query("SELECT * FROM categories");
    if($res2){ while($r = $res2->fetch_assoc()) $cats[] = $r; $res2->close(); }
    ?>
    <!doctype html><html lang="ar"><head><meta charset="utf-8"><title>إدارة المنتج</title>
    <link rel="stylesheet" href="styles.css"></head><body>
    <div class="container">
      <h3><?= $action === 'new' ? 'إضافة منتج' : 'تعديل منتج' ?></h3>
      <form method="post" enctype="multipart/form-data" action="admin_actions.php?action=save">
        <input type="hidden" name="id" value="<?= $id ?>">
        <label>الاسم (عربي)</label>
        <input class="input-transparent" name="name_ar" value="<?= htmlspecialchars($product['name_ar']) ?>" required>
        <label>الوصف</label>
        <textarea class="input-transparent" name="description_ar"><?= htmlspecialchars($product['description_ar']) ?></textarea>
        <label>السعر</label>
        <input class="input-transparent" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>
        <label>المخزون</label>
        <input class="input-transparent" name="stock" value="<?= htmlspecialchars($product['stock']) ?>" required>
        <label>الفئة</label>
        <select name="category_id" class="input-transparent" required>
          <?php foreach($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']==$product['category_id']?'selected':'' ?>><?= htmlspecialchars($c['name_ar']) ?></option>
          <?php endforeach; ?>
        </select>

        <label>اللون</label><input class="input-transparent" name="color" value="<?= htmlspecialchars($product['color'] ?? '') ?>">
        <label>الأبعاد</label><input class="input-transparent" name="dimensions" value="<?= htmlspecialchars($product['dimensions'] ?? '') ?>">
        <label>المادة</label><input class="input-transparent" name="material" value="<?= htmlspecialchars($product['material'] ?? '') ?>">
        <label>الوزن</label><input class="input-transparent" name="weight" value="<?= htmlspecialchars($product['weight'] ?? '') ?>">
        <label>الضمان</label><input class="input-transparent" name="warranty" value="<?= htmlspecialchars($product['warranty'] ?? '') ?>">

        <label>صورة المنتج (رفع)</label>
        <input type="file" name="image">
        <div style="margin-top:8px"><button class="btn" type="submit">حفظ</button></div>
      </form>
    </div>
    </body></html>
    <?php
    exit;
}
          // حفظ المنتج
if($action === 'save' && $_SERVER['REQUEST_METHOD']==='POST'){
    $id = sanitize_int($_POST['id'] ?? 0);
    $name = sanitize_text($_POST['name_ar'] ?? '');
    $desc = sanitize_textarea($_POST['description_ar'] ?? '');
    $price = sanitize_float($_POST['price'] ?? 0);
    $stock = sanitize_int($_POST['stock'] ?? 0);
    $cat = sanitize_int($_POST['category_id'] ?? 0);
    $color = sanitize_text($_POST['color'] ?? '');
    $dimensions = sanitize_text($_POST['dimensions'] ?? '');
    $material = sanitize_text($_POST['material'] ?? '');
    $weight = sanitize_text($_POST['weight'] ?? '');
    $warranty = sanitize_text($_POST['warranty'] ?? '');
//
    $image = $product_image = '';
    if(isset($_FILES['image']) && isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name']){
        $fn = basename($_FILES['image']['name']);
        $fn = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fn);
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__.'/images/'.$fn);//
        $image = $fn;
    }
//
    if($id>0){ 
        if($image){
            $stmt = $mysqli->prepare("UPDATE products SET name_ar=?,description_ar=?,price=?,stock=?,category_id=?,image=?,color=?,dimensions=?,material=?,weight=?,warranty=? WHERE id=?");
            $stmt->bind_param('ssdisssssssi',$name,$desc,$price,$stock,$cat,$image,$color,$dimensions,$material,$weight,$warranty,$id);
        } else { 
            $stmt = $mysqli->prepare("UPDATE products SET name_ar=?,description_ar=?,price=?,stock=?,category_id=?,color=?,dimensions=?,material=?,weight=?,warranty=? WHERE id=?");
            $stmt->bind_param('ssdiiissssi',$name,$desc,$price,$stock,$cat,$color,$dimensions,$material,$weight,$warranty,$id);
        }
        $stmt->execute(); $stmt->close();
        $_SESSION['admin_success'] = "تم تعديل المنتج بنجاح: " . htmlspecialchars($name);
    } else {
        $imgVal = $image ?? '';
        $stmt = $mysqli->prepare("INSERT INTO products (name_ar,description_ar,price,stock,category_id,image,color,dimensions,material,weight,warranty) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('ssdisssssss', $name,$desc,$price,$stock,$cat,$imgVal,$color,$dimensions,$material,$weight,$warranty);
        $stmt->execute();
        $stmt->close();
        $_SESSION['admin_success'] = "تمت إضافة المنتج بنجاح: " . htmlspecialchars($name); 
    }
    header("Location: admin.php?tab=products"); //
    exit;
}

if($action === 'delete'){
    $id = sanitize_int($_GET['id'] ?? 0);
    if($id>0){
        $stmt = $mysqli->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param('i',$id);
        $stmt->execute(); $stmt->close();
    }
    header("Location: admin.php?tab=products");
    exit;
}

// حذف طلب (admin)
if($action === 'delete_order'){
    $id = sanitize_int($_GET['id'] ?? 0);
    if($id>0){
        $stmt = $mysqli->prepare("DELETE FROM orders WHERE id=?");
        $stmt->bind_param('i',$id);
        $stmt->execute(); $stmt->close();
    }
    header("Location: admin.php?tab=orders");
    exit;
}

// تغيير حالة الطلب (admin)
if($action === 'change_status'){
    $id = sanitize_int($_GET['id'] ?? 0);
    $status = sanitize_text($_GET['status'] ?? '');
    $allowed = ['قيد التجهيز','مكتمل','غير مكتمل','تم الإلغاء'];
    if(!in_array($status, $allowed)){
        redirect_with_msg('admin.php?tab=orders', 'حالة غير مسموح بها.');
    }

    // إذا نريد عند التعيين إلى مكتمل تحديث المخزون (في حال لم يتم تحديثها قبلاً)
    if($status === 'مكتمل'){
        // جلب حالة stock_updated من orders
        $stmtCheck = $mysqli->prepare("SELECT stock_updated FROM orders WHERE id=? LIMIT 1");
        $stmtCheck->bind_param('i',$id);
        $stmtCheck->execute();
        $r = $stmtCheck->get_result();
        $row = $r->fetch_assoc();
        $stmtCheck->close();
        $stockUpdated = $row['stock_updated'] ?? 0;

        if(!$stockUpdated){
            // نفتح معاملة ونحاول تحديث المخزون اعتمادًا على عناصر الطلب
            $mysqli->begin_transaction();
            try {
                $stmtItems = $mysqli->prepare("SELECT product_id, quantity FROM order_items WHERE order_id=?");
                $stmtItems->bind_param('i',$id);
                $stmtItems->execute();
                $resItems = $stmtItems->get_result();
                $items = $resItems->fetch_all(MYSQLI_ASSOC);
                $stmtItems->close();

                // تحقق من المخزون الحالي لكل منتج
                $shortages = [];
                foreach($items as $it){
                    $pid = intval($it['product_id']);
                    $need = intval($it['quantity']);
                    $stmtP = $mysqli->prepare("SELECT stock FROM products WHERE id=? FOR UPDATE");
                    $stmtP->bind_param('i',$pid);
                    $stmtP->execute();
                    $rP = $stmtP->get_result();
                    $prod = $rP->fetch_assoc();
                    $stmtP->close();
                    $current = $prod['stock'] ?? 0;
                    if($current < $need){
                        $shortages[] = "المنتج #{$pid} (المطلوب: {$need} - المتوفر: {$current})";
                    }
                }

                if(!empty($shortages)){
                    $mysqli->rollback();
                    redirect_with_msg('admin.php?tab=orders', 'لا يمكن تحديث المخزون عند إتمام الطلب بسبب نفاد بعض العناصر: ' . implode('; ', $shortages));
                }

                // كل شيء جيد؛ نحدّث المخزون
                $upd = $mysqli->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                foreach($items as $it){
                    $qty = intval($it['quantity']);
                    $pid = intval($it['product_id']);
                    $upd->bind_param('ii', $qty, $pid);
                    $upd->execute();
                }
                $upd->close();

                // علّم الطلب بأنه تم تحديث المخزون
                $stmtU = $mysqli->prepare("UPDATE orders SET stock_updated = 1 WHERE id=?");
                $stmtU->bind_param('i',$id);
                $stmtU->execute();
                $stmtU->close();

                $mysqli->commit();
            } catch(Exception $e){
                $mysqli->rollback();
                redirect_with_msg('admin.php?tab=orders', 'خطأ عند تحديث المخزون: ' . $e->getMessage());
            }
        }
    }

    //  تغيير الحالة
    $stmt = $mysqli->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute(); $stmt->close();
    header("Location: admin.php?tab=orders");
    exit;
}
// عرض تفاصيل الطلب (admin)
if($action === 'view_order'){
    $id = sanitize_int($_GET['id'] ?? 0);
    if($id<=0){ redirect_with_msg('admin.php?tab=orders','طلب غير صالح.'); }
    $stmt = $mysqli->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=?");
    $stmt->bind_param('i',$id);
    $stmt->execute();
    $res = $stmt->get_result();
    $o = $res->fetch_assoc();
    $stmt->close();

    $items = [];
    $stmt2 = $mysqli->prepare("SELECT * FROM order_items WHERE order_id=?");
    $stmt2->bind_param('i',$id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if($res2){ while($r = $res2->fetch_assoc()) $items[] = $r; $res2->close(); }
    ?>

    <!doctype html><html lang="ar"><head><meta charset="utf-8"><title>عرض الطلب</title>
    <link rel="stylesheet" href="styles.css"></head><body>
    <div class="container">
      <h3>تفاصيل الطلب <?= htmlspecialchars($o['invoice_number']) ?></h3>
      <p>المستخدم: <?= htmlspecialchars($o['username']) ?></p>
      <p>الحالة: <?= htmlspecialchars($o['status']) ?></p>
      <table class="table">
        <thead><tr><th>المنتج</th><th>الكمية</th><th>سعر الوحدة</th><th>المجموع</th></tr></thead>
        <tbody>
          <?php foreach($items as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['product_name_ar']) ?></td>
              <td><?= $it['quantity'] ?></td>
              <td><?= $it['unit_price'] ?></td>
              <td><?= $it['subtotal'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p>العنوان: <?= htmlspecialchars($o['address']) ?></p>
      <p>الهاتف: <?= htmlspecialchars($o['phone']) ?> - <?= htmlspecialchars($o['phone_alt']) ?></p>
      <p><a href="admin.php?tab=orders">عودة</a></p>
    </div></body></html>
    <?php
    exit;
}

echo "إجراء غير معروف.";
?>