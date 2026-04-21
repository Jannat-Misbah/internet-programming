<?php
require_once 'functions.php';
header('Content-Type: application/json');
if(!is_logged_in()) {
    echo json_encode(['success'=>false,'message'=>'يجب تسجيل الدخول']);
    exit;
}
$user_id = current_user_id();
$action = $_POST['action'] ?? '';
//اضافة منتج للسلة
if($action === 'add'){
    // كما كان سابقاً...
    $pid = intval($_POST['product_id']);
    $stmt = $mysqli->prepare("SELECT id, quantity FROM cart_items WHERE user_id=? AND product_id=?");
    $stmt->bind_param('ii',$user_id,$pid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if($row){//المنتج موجود في السلة ... و زيادة المنتج بواحد
        $stmt2 = $mysqli->prepare("UPDATE cart_items SET quantity = quantity+1 WHERE id=?");
        $stmt2->bind_param('i', $row['id']);
        $stmt2->execute(); $stmt2->close();
    } else {
        $stmt2 = $mysqli->prepare("INSERT INTO cart_items (user_id,product_id,quantity) VALUES (?, ?, 1)");
        $stmt2->bind_param('ii',$user_id,$pid);
        $stmt2->execute(); $stmt2->close();
    }
    echo json_encode(['success'=>true,'message'=>'تمت إضافة المنتج إلى السلة']);
    exit;
}
//تحدث كمية منتج في السلة
if($action === 'update'){
    $id = intval($_POST['id']);
    $qty = max(1,intval($_POST['quantity']));
    $stmt = $mysqli->prepare("UPDATE cart_items SET quantity=? WHERE id=? AND user_id=?");
    $stmt->bind_param('iii',$qty,$id,$user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>true,'message'=>'تم تحديث الكمية']);
    exit;
}
//حذف عنصرر من السلة
if($action === 'remove'){
    $id = intval($_POST['id'] ?? 0);
    if($id <= 0){
        echo json_encode(['success'=>false,'message'=>'طلب خاطئ']);
        exit;
    }
    // تحقّق أن سطر السلة يخص المستخدم قبل الحذف
    $stmt = $mysqli->prepare("SELECT id FROM cart_items WHERE id=? AND user_id=? LIMIT 1");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if(!$row){//لم يجد العنصر او  مايخصش المستخدم
        echo json_encode(['success'=>false,'message'=>'العنصر غير موجود أو لا تملك صلاحية حذفه']);
        exit;
    }//الحذف نهائيا يكون بعد التحقق من الصلاحية
    $del = $mysqli->prepare("DELETE FROM cart_items WHERE id=?");
    $del->bind_param('i',$id);
    $del->execute(); $del->close();
    echo json_encode(['success'=>true,'message'=>'تم حذف العنصر من السلة']);
    exit;
}

echo json_encode(['success'=>false,'message'=>'إجراء غير معروف']);
?>