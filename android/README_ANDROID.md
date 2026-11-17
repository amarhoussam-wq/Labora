# LABORA — تشغيل تطبيق المريض للأندرويد (Android App Guide)

يوفر مشروع LABORA مسارين متكاملين لتجربة وعرض تطبيق المريض أمام لجنة مسابقة **«درع مقاول ناشئ»**:

---

## 1. تجربة فورية كـ Progressive Web App (PWA) على أي هاتف Android
- افتح متصفح Google Chrome على هاتفك.
- اتصل بنفس شبكة Wi-Fi واكتب عنوان السيرفر المحلي:
  ```text
  http://[IP-حاسوبك]/labora/patient/book.php
  ```
- انقر على القائمة (ثلاث نقاط) ثم اختر: **"إضافة إلى الشاشة الرئيسية" (Add to Home screen) أو "تثبيت التطبيق" (Install App)**.
- سيتم تثبيت LABORA كأيقونة تطبيق مستقل على هاتفك بملء الشاشة، مع إمكانية رفع الوصفة من كاميرا الهاتف مباشرة.

---

## 2. تشغيل كود مشروع Android Studio الأصيل
- مجلد المشروع موجود في: `android/`
- افتح **Android Studio** واختر `Open an Existing Project` وحدد مجلد `android`.
- في ملف `MainActivity.kt`:
  - إذا كنت تستخدم محاكي الأندرويد (Emulator)، العنوان الافتراضي هو:
    `http://10.0.2.2/labora/patient/book.php`
  - إذا كنت تستخدم هاتفاً حقيقياً موصولاً بالـ USB، استبدل `10.0.2.2` بـ IP حاسوبك (مثال: `http://192.168.1.5/labora/patient/book.php`).
- اضغط على **Run** لتشغيل التطبيق أو **Build > Build Bundle(s) / APK(s) > Build APK(s)** لتوليد ملف `labora.apk`.

---

## الحسابات التجريبية الجاهزة للاختبار (كلمة المرور: demo123):
- **المريض (Patient):** `patient@labora.dz`
- **المخبر 1 (Ibn Sina):** `lab1@labora.dz`
- **المخبر 2 (Chifa):** `lab2@labora.dz`
