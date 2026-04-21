<?php
require_once 'functions.php';
require_once 'validation_functions.php';

$notice = $_GET['msg'] ?? '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])){
    if($_POST['action'] === 'login'){
        $username = sanitize_text($_POST['username'] ?? '');
        $password = sanitize_text($_POST['password'] ?? '');

        // تحقق بسيط
        if(!required_field($username) || !required_field($password)){
            $error = "الرجاء ملء الحقول المطلوبة.";
        } else {
            $stmt = $mysqli->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->bind_param('ss', $username, $username);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();

            // مقارنة نصية مباشرة (  بتخزين كلمات بصيغة صريحة)___ وحفظ البيانات في الجلسة
            if($user && $password === $user['password_hash']){
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                if($user['role'] === 'admin') redirect('admin.php');
                redirect('index.php');
            } else {
                $error = "بيانات الدخول غير صحيحة.";
            }
        }
    } elseif($_POST['action'] === 'register'){
        $username = sanitize_text($_POST['username'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text($_POST['phone'] ?? '');
        $password = sanitize_text($_POST['password'] ?? '');

        if(!required_field($username) || !required_field($email) || !required_field($phone) || !required_field($password)){
            $error = "الرجاء ملء الحقول المطلوبة.";
        } else {
            // تحقق من الوجود
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
            $stmt->bind_param('ss',$username,$email);
            $stmt->execute();
            $res = $stmt->get_result();
            if($res->fetch_assoc()){
                $error = "اسم المستخدم أو البريد مسجل مسبقًا.";
            } else {
                $role = 'user';
                $emptyAddress = '';
                // تخزين كلمة المرور كنص واضح
                $ins = $mysqli->prepare("INSERT INTO users (username,email,password_hash,role,phone,address) VALUES (?,?,?,?,?,?)");
                $ins->bind_param('ssssss',$username,$email,$password,$role,$phone,$emptyAddress);
                $ok = $ins->execute();
                $ins->close();
                if($ok){
                    $success = "تم إنشاء الحساب. يمكنك تسجيل الدخول الآن.";
                } else {
                    $error = "حدث خطأ أثناء إنشاء الحساب.";
                }
            }
            $stmt->close();
        }
    }
}
?>

<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8">
<title>تسجيل الدخول - متجر الأثاث</title>
<link rel="stylesheet" href="styles.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
  <div class="login-page" style="background-image:url('images/loginbackg.jpg')">
    <div class="login-box">
      <h2>تسجيل الدخول / إنشاء حساب</h2>

      <p><a href="index.php" class="link-home">الرئيسية</a></p>

      <?php if($notice): ?>
        <div style="background:#fff3cd;padding:8px;border-radius:6px;margin-bottom:8px;color:#856404">
          <?= htmlspecialchars($notice) ?>
        </div>
      <?php endif; ?>

      <?php if(!empty($error)): ?><div style="color:#b00020"><?= $error ?></div><?php endif;?>
      <?php if(!empty($success)): ?><div style="color:green"><?= $success ?></div><?php endif;?>

        <!-- نموذج الدخول -->

      <form id="loginForm" method="post">
        <input type="hidden" name="action" value="login">  <!--  -->
        <input class="input-transparent" name="username" placeholder="اسم المستخدم أو البريد" required>
        <input class="input-transparent" name="password" type="password" placeholder="كلمة المرور" required>
        <div style="display:flex;gap:8px;margin-top:8px">
          <button class="btn" type="submit">دخول</button>
          <button id="showReg" type="button" class="btn btn-muted">إنشاء حساب</button>
        </div>
      </form>

      <hr>

      <div id="reg" style="display:none">
        <h3>إنشاء حساب جديد</h3>
        <form id="regForm" method="post">
          <input type="hidden" name="action" value="register">
          <input class="input-transparent" name="username" placeholder="اسم المستخدم" required>
          <input class="input-transparent" name="email" placeholder="البريد الإلكتروني" required>
          <input class="input-transparent" name="phone" placeholder="رقم الهاتف" required>
          <input class="input-transparent" name="password" type="password" placeholder="كلمة المرور" required>
          <div style="margin-top:8px">
            <button class="btn" type="submit">تسجيل</button>
            <button id="hideReg" type="button" class="btn btn-muted">إلغاء</button>
          </div>
        </form>
      </div>

    </div>
  </div>

<script>
$(function(){
  $('#showReg').on('click', function(){ $('#reg').show(); $('html,body').animate({scrollTop: $('#reg').offset().top - 50},200); });
  $('#hideReg').on('click', function(){ $('#reg').hide(); });
});
</script>
</body>
</html>