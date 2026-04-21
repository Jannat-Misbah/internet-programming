
$(function(){ // document. ready
  console.log('[scripts] loaded, isLoggedIn=', typeof isLoggedIn !== 'undefined' ? isLoggedIn : 'undefined');
// التأكد أن الملف حمّل وأننا نعرف إذا كان المستخدم مسجل دخول
 
// معاملات العرض (sidebar)
  $('#offersToggle').on('click', function(){
    $('#offersSidebar').show().addClass('open').attr('aria-hidden','false');  //أظهر القائمة الجانبية>>>>>
    $('#sidebarOverlay').show().addClass('show'); //
  });
  $('#closeSidebar, #sidebarOverlay').on('click', function(){//
    $('#offersSidebar').hide().removeClass('open').attr('aria-hidden','true'); //يخفي العنصر مع اخقاء القائمة الجانبية 
    $('#sidebarOverlay').hide().removeClass('show');
  });
  $(document).on('click', '.sidebar-item', function(){//النقر على عنصر في الشريط الجانبي
    $('.sidebar-item').removeClass('active');//يحذ التحديد ... الصنف الاكتف
    $(this).addClass('active');//يحدد القسم الذي نقر عليه
    const cat = $(this).data('id') || '';
    const q = $('#q').val() || ''; //الحثول على قيمة البحث
    $.get('index.php', {ajax:1, q:q, category:cat}, function(html){//المنتجات عبر الاجاكس بحيث يرسل كلمة البحث و رقم الفئة و النتيجة الي يرجعها السيرفر تظهر على html
      $('#productsWrap').html(html);//يرد التيجة في مكانها ... يحدث قسم المنتجات يالمحتوى الجديد
    });
    $('#offersSidebar').hide();
    $('#sidebarOverlay').hide();
  });

  // اعتراض نقرات أيقونات تتطلب تسجيل الدخول
  $(document).on('click', '.requires-login', function(e){
    e.preventDefault();// يوقف السلوك الافتراضي  حتى نمنع المستخدم انه ينتقل للصقة من غير تسجيل دخول
    const href = $(this).data('href') || '';
    const msg = $(this).data('msg') || 'يجب تسجيل الدخول أولاً';
    console.log('[scripts] requires-login click, isLoggedIn=', isLoggedIn, 'href=', href);
    if(typeof isLoggedIn === 'undefined' || !isLoggedIn){//تحقق من تسجيل الدخول
      // إعادة توجيه الزائر إلى صفحة تسجيل الدخول مع رسالة
      window.location.href = 'login.php?msg=' + encodeURIComponent(msg);//تشفيرالرسالة في الرابط
      return;
    }
    // المستخدم مسجل: تابع إلى href إن وُجد
    if(href && href !== '#') {//اذا كان المستخدم مسجلا ,يذهب الى الرابط
      window.location.href = href;
    } else {
      // إذا كان href='#' ربما نفتح مودال أو نُنفّذ فعل آخر
      console.log('[scripts] no href to follow');
    }
  });
  //................... ادارة سلة التسوق و المفضلة
$(function(){
  // حذف عنصر من السلة
  $(document).on('click', '.delete-cart-item', function(){
    const rowId = $(this).data('id');//ياخد رقم الصف في السلة
    if(!confirm('هل أنت متأكد من حذف هذا المنتج من السلة؟')) return;
    $.post('cart_actions.php', {action:'remove', id: rowId}, function(res){//يرسل طلب حذف المنتج من السلة...يرسل بيانات للسيرقر  
      if(res.success){
        // حذف صف السلة من DOM أو إعادة تحميل الصفحة
        $('#cart-row-' + rowId).remove();
        // إعادة حساب إجمالي عبر إعادة تحميل الصفحة أو استدعاء AJAX لحساب جديد
        location.reload();//يعاود يحمل الصفحة
      } else {
        alert(res.message);
      }
    }, 'json').fail(function(){ alert('فشل التواصل مع الخادم'); }); //اذا فشل الاتصال بالسيرفر
  });

  // ... باقي معالجات add-to-cart, add-to-fav, cart-qty ...
});
  // إضافة للسلة و المفضلة و تحديث الكميات
  $(document).on('click', '.add-to-cart', function(){
    const pid = $(this).data('id'); //رقم المنتج
    $.post('cart_actions.php', {action:'add', product_id:pid}, function(res){ //يرسل طلب اضافة المنتج الى السلة
      alert(res.message);
      if(res.success) location.reload();//يعاود تحميل الصفحة لو تمت الاضافة بنجاح
    }, 'json');
  });
  $(document).on('click', '.add-to-fav', function(){
    const pid = $(this).data('id');
    $.post('fav_actions.php', {action:'toggle', product_id:pid}, function(res){ //بدل بين الاضافة و الازالة toggle
      alert(res.message);
      if(res.success) location.reload();
    }, 'json');
  });
  $(document).on('change', '.cart-qty', function(){
    const rowId = $(this).data('id');
    const qty = $(this).val();
    $.post('cart_actions.php', {action:'update', id:rowId, quantity:qty}, function(res){//يرسل للسيرفر عند تغيير رقم في حقل الكمية
      if(res.success) location.reload();
      else alert(res.message);
    }, 'json');
  });

});