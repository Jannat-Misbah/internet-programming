<?php
require_once 'functions.php';
require_once 'validation_functions.php';

if(!is_logged_in()){
    redirect_with_msg('login.php', 'يجب تسجيل الدخول أولاً لإتمام الشراء');
}
$user_id = current_user_id();

if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout'){
    // جلب محتويات السلة مع product_id و quantity
    $stmt = $mysqli->prepare("SELECT ci.product_id, ci.quantity, p.name_ar, p.price, p.stock FROM cart_items ci JOIN products p ON ci.product_id=p.id WHERE ci.user_id=?");
    $stmt->bind_param('i',$user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if(!$items){
        redirect_with_msg('cart.php', 'السلة فارغة.');
    }

    // تنقية بيانات الشحن الأساسية (إنها ترسل من cart.php أو form)
    $name = sanitize_text($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text($_POST['phone'] ?? '');
    $phone_alt = sanitize_text($_POST['phone_alt'] ?? '');
    $address = sanitize_textarea($_POST['address'] ?? '');
    $notes = sanitize_textarea($_POST['notes'] ?? '');

    if(!$name || !$email || !$phone || !$address){
        redirect_with_msg('cart.php', 'الرجاء ملء الحقول الإلزامية.');
    }

    // نبدأ المعاملة
    $mysqli->begin_transaction();

    try {
        // تحقق المخزون مع قفل الصفوف
        $shortages = [];
        foreach($items as $it){
            $pid = intval($it['product_id']);
            $stmt2 = $mysqli->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
            $stmt2->bind_param('i', $pid);
            $stmt2->execute();
            $r2 = $stmt2->get_result();
            $pRow = $r2->fetch_assoc();
            $stmt2->close();
            $currentStock = $pRow['stock'] ?? 0;
            if($it['quantity'] > $currentStock){
                $shortages[] = $it['name_ar'] . ' (المطلوب: ' . $it['quantity'] . ' - المتوفر: ' . $currentStock . ')';
            }
        }

        if(!empty($shortages)){
            $mysqli->rollback();
            $msg = 'بعض العناصر غير متوفرة بالكميات المطلوبة: ' . implode('; ', $shortages);
            redirect_with_msg('cart.php', $msg);
        }

        // الكميات متاحة — إنشاء الطلب
        $total = 0;
        foreach($items as $it) $total += $it['quantity'] * $it['price'];
        $invoice_no = generate_invoice_number();

        $ins = $mysqli->prepare("INSERT INTO orders (user_id, invoice_number, total_amount, phone, phone_alt, address, notes, status, stock_updated) VALUES (?, ?, ?, ?, ?, ?, ?, 'قيد التجهيز', 1)");
        $ins->bind_param('isdssss', $user_id, $invoice_no, $total, $phone, $phone_alt, $address, $notes);
        $ins->execute();
        $order_id = $mysqli->insert_id;
        $ins->close();

        // إضافة عناصر الطلب وتخفيض المخزون
        $stmtIt = $mysqli->prepare("INSERT INTO order_items (order_id,product_id,product_name_ar,unit_price,quantity,subtotal) VALUES (?, ?, ?, ?, ?, ?)");//
        $updProd = $mysqli->prepare("UPDATE products SET stock = stock - ? WHERE id = ?"); 
        foreach($items as $it){
            $sub = $it['price'] * $it['quantity'];
            $pid = intval($it['product_id']);
            $pname = $it['name_ar'];
            $price = $it['price'];
            $qty = $it['quantity'];

            $stmtIt->bind_param('iisdid', $order_id, $pid, $pname, $price, $qty, $sub);
            $stmtIt->execute();

            // تحديث المخزون
            $updProd->bind_param('ii', $qty, $pid);
            $updProd->execute();
        }
        $stmtIt->close();
        $updProd->close();

        // تحديث بيانات المستخدم (حفظ الهاتف والعنوان) - اختياري
        $up = $mysqli->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
        $up->bind_param('ssi', $phone, $address, $user_id);
        $up->execute(); $up->close();

        // حذف السلة
        $del = $mysqli->prepare("DELETE FROM cart_items WHERE user_id=?");
        $del->bind_param('i', $user_id);
        $del->execute(); $del->close();

        $mysqli->commit();

        redirect_with_msg('my_orders.php', 'تم إنشاء الطلب بنجاح ورقمه: ' . $invoice_no);
    } catch(Exception $e){
        $mysqli->rollback();
        redirect_with_msg('cart.php', 'حدث خطأ أثناء معالجة طلبك: ' . $e->getMessage());
    }
}
?>