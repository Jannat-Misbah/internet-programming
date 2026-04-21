<?php
require_once 'functions.php';
require_once 'validation_functions.php';

// ======== استخدام TCPDF ========
if(!file_exists(__DIR__.'/tcpdf/tcpdf.php')){
    exit('مطلوب مكتبة TCPDF في المجلد. حمّلها من https://github.com/tecnickcom/TCPDF');
}
require_once __DIR__.'/tcpdf/tcpdf.php';

if(!is_logged_in()){
    redirect_with_msg('login.php','يجب تسجيل الدخول لعرض الفاتورة');
}

$id = sanitize_int($_GET['id'] ?? 0);
if($id<=0) redirect_with_msg('my_orders.php','فاتورة غير صالحة');

$stmt = $mysqli->prepare("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=? LIMIT 1");
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();
if(!$order) redirect_with_msg('my_orders.php','الطلب غير موجود');

// السماح فقط لمالك الفاتورة أو الادمن
$current = current_user();
if($current['id'] != $order['user_id'] && !is_admin()){
    redirect_with_msg('my_orders.php','ليس لديك صلاحية لعرض هذه الفاتورة');
}

// جلب العناصر
$stmt2 = $mysqli->prepare("SELECT product_name_ar, unit_price, quantity, subtotal FROM order_items WHERE order_id=?");
$stmt2->bind_param('i',$id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$items = $res2->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// ======== إنشاء PDF باستخدام TCPDF ========
// معلمات TCPDF: (الاتجاه، الوحدة، الحجم، UTF-8، الترميز الداخلي، القرص المؤقت)
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// إزالة الهيدر والفوتر الافتراضي
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// إضافة صفحة
$pdf->AddPage();

// ======== كتابة المحتوى ========
// العنوان
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, 'فاتورة شراء', 0, 1, 'C');
$pdf->Ln(4);

// معلومات الفاتورة
$pdf->SetFont('dejavusans', '', 12);
$pdf->Cell(0, 6, 'رقم الفاتورة: ' . $order['invoice_number'], 0, 1);
$pdf->Cell(0, 6, 'التاريخ: ' . $order['created_at'], 0, 1);
$pdf->Cell(0, 6, 'المستخدم: ' . $order['username'] . ' - ' . $order['email'], 0, 1);
$pdf->Ln(6);

// جدول العناصر - العناوين
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->Cell(90, 7, 'المنتج', 1, 0, 'C');
$pdf->Cell(25, 7, 'الكمية', 1, 0, 'C');
$pdf->Cell(35, 7, 'سعر الوحدة', 1, 0, 'C');
$pdf->Cell(40, 7, 'المجموع', 1, 1, 'C');

// جدول العناصر - المحتوى
$pdf->SetFont('dejavusans', '', 11);
foreach($items as $it) {
    // TCPDF يدعم UTF-8 مباشرة، لا داعي لـ iconv
    $pdf->Cell(90, 6, $it['product_name_ar'], 1, 0, 'C');
    $pdf->Cell(25, 6, $it['quantity'], 1, 0, 'C');
    $pdf->Cell(35, 6, $it['unit_price'] . ' LD', 1, 0, 'C');
    $pdf->Cell(40, 6, $it['subtotal'] . ' LD', 1, 1, 'C');
}

$pdf->Ln(4);

// المجموع الكلي
$pdf->SetFont('dejavusans', 'B', 12);
$total = $order['total_amount'] ?? 0;
$pdf->Cell(0, 8, 'المجموع الكلي: ' . $total . ' LD', 0, 1, 'R');
$pdf->Ln(8);

// بيانات الشحن
$pdf->SetFont('dejavusans', '', 11);
$pdf->Cell(0, 6, 'بيانات الشحن:', 0, 1);

// TCPDF يدعم UTF-8 مباشرة، لا حاجة لـ iconv
if(!empty($order['phone']) || !empty($order['phone_alt'])) {
    $phones = 'الهاتف: ' . ($order['phone'] ?? '') . 
              (!empty($order['phone_alt']) ? ' - الهاتف الاحتياطي: ' . $order['phone_alt'] : '');
    $pdf->MultiCell(0, 6, $phones, 0, 1);
}

if(!empty($order['address'])) {
    $pdf->MultiCell(0, 6, 'العنوان: ' . $order['address'], 0, 1);
}

if(!empty($order['notes'])) {
    $pdf->MultiCell(0, 6, 'ملاحظات: ' . $order['notes'], 0, 1);
}

$filename = 'invoice_' . $order['invoice_number'] . '.pdf';

$pdf->Output($filename, 'D');

exit;
?>