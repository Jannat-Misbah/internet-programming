<?php
require_once 'functions.php';
header('Content-Type: application/json');
if(!is_logged_in()){
    echo json_encode(['success'=>false,'message'=>'يجب تسجيل الدخول']);
    exit;
}
$user_id = current_user_id();
$action = $_POST['action'] ?? '';
if($action === 'toggle'){
    $pid = intval($_POST['product_id']);
    $stmt = $mysqli->prepare("SELECT id FROM favorites WHERE user_id=? AND product_id=?");
    $stmt->bind_param('ii',$user_id,$pid);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->fetch_assoc()){
        $stmt->close();
        $del = $mysqli->prepare("DELETE FROM favorites WHERE user_id=? AND product_id=?");
        $del->bind_param('ii',$user_id,$pid);
        $del->execute(); $del->close();
        echo json_encode(['success'=>true,'message'=>'أزيل من المفضلة']);
    } else {
        $stmt->close();
        $ins = $mysqli->prepare("INSERT INTO favorites (user_id,product_id) VALUES (?, ?)");
        $ins->bind_param('ii',$user_id,$pid);
        $ins->execute(); $ins->close();
        echo json_encode(['success'=>true,'message'=>'أضيف للمفضلة']);
    }
    exit;
}
echo json_encode(['success'=>false,'message'=>'إجراء غير معروف']);
?>