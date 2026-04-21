<?php
// دوال مساعدة    

// تنقية نص عام: trim، إزالة الوسوم، تحويل الرموز الخاصة
function sanitize_text($input) {
    if ($input === null) return '';
    $s = trim($input); //spaces from end and start 
    // إزالة الوسوم الضارة
    $s = strip_tags($s);
    // استبدال أحرف التحكم-تستبدل النصوص باستخدام التتعابير النمطية
    $s = preg_replace('/[\x00-\x1F\x7F]/u', '', $s);
    // ترميز HTML خاص
    $s = htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    // ENT_QUOTES: تحويل الكوتيشنز دبل او سنقل
    // ENT_SUBSTITUTE: استبدال الأحرف غير الصالحة  
    return $s;
}

// تنقية نصوص أطول (مثل العنوان/الوصف) مع السماح ببعض الوسوم البسيطة ت
function sanitize_textarea($input) {
    if ($input === null) return '';
    $s = trim($input);
    // يمكنك السماح ببعض الوسوم الآمنة أو استخدام strip_tags
    $s = strip_tags($s); //
    $s = htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $s;
}

// تنقية البريد الإلكتروني
function sanitize_email($input) {
    $s = trim($input);
    $s = filter_var($s, FILTER_SANITIZE_EMAIL);
    if (filter_var($s, FILTER_VALIDATE_EMAIL)) return $s;
    return ''; 
}

// تحويل إلى عدد صحيح آم��
function sanitize_int($input, $default = 0) {
    if ($input === null) return $default;
    return filter_var($input, FILTER_VALIDATE_INT) !== false ? intval($input) : $default;
}

// تحويل إلى float آمن
function sanitize_float($input, $default = 0.0) {
    if ($input === null) return $default;
    $f = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    //FILTER_SANITIZE_NUMBER_FLOAT: إزالة كل شيء إلا الأرقام، +، -، .
    // FILTER_FLAG_ALLOW_FRACTION:يخلي الفاصلة العشرية 
    return $f === '' ? $default : floatval($f); 
}

// تحقق من وجود الحقل (غير فارغ بعد التنقية)
function required_field($value) {
    return ($value !== null && $value !== '' && strlen(trim($value)) > 0);
}
?>