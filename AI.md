Bu dosya, projenin geliştirilmesi sırasında karşılaşılan ve çözülen 10 önemli teknik problemin çözümünü içerir.

---

### **Soru 1: Kullanıcıdan alınan verilerle SQL sorgusu oluştururken SQL Injection saldırılarından nasıl korunulur?**
**Cevap/Çözüm:** SQL Injection'ı önlemenin en güvenli yolu, kullanıcı girdilerini doğrudan SQL sorgu metnine eklemek yerine **Prepared Statements (Hazırlanmış İfadeler)** kullanmaktır. Projede `mysqli` kütüphanesi tercih edildiği için, `prepare()`, `bind_param()` ve `execute()` metodları kullanılarak bu koruma sağlandı. Bu yöntem, SQL komutları ile kullanıcı verisini birbirinden tamamen ayırır, böylece kullanıcı tarafından girilen özel karakterlerin sorgunun yapısını bozması engellenir.

---

### **Soru 2: Veritabanından çekilen ve kullanıcı tarafından girilmiş veriler ekranda gösterilirken Cross-Site Scripting (XSS) zafiyetleri nasıl engellenir?**
**Cevap/Çözüm:** XSS zafiyetini engellemek için, veritabanından gelen ve HTML içinde ekrana basılacak olan tüm dinamik veriler `htmlspecialchars()` fonksiyonundan geçirildi. Bu fonksiyon, `<` , `>` , `"` gibi HTML için özel anlam taşıyan karakterleri zararsız HTML entity'lerine (`&lt;`, `&gt;`, `&quot;`) dönüştürür. Bu sayede, kullanıcı forma zararlı bir JavaScript kodu girse bile, bu kod tarayıcı tarafından bir script olarak çalıştırılmaz, sadece ekranda zararsız bir metin olarak görünür.

---

### **Soru 3: Bir kullanıcı giriş yaptıktan sonra, oturumun güvenliğini artırmak için hangi ek önlem alınmalıdır?**
**Cevap/Çözüm:** Kullanıcı adı ve parola doğrulandıktan hemen sonra, "Session Fixation" (Oturum Sabitleme) saldırılarını önlemek için `session_regenerate_id(true)` fonksiyonu çağrıldı. Bu fonksiyon, mevcut oturum verilerini korurken, kullanıcıya tamamen yeni bir oturum kimliği (Session ID) atar ve eski oturum kimliğini geçersiz kılar. Bu sayede, kimlik doğrulamadan önce ele geçirilmiş bir oturum kimliği, giriş yapıldıktan sonra kullanılamaz hale gelir.

---

### **Soru 4: `is_emirleri` tablosu binlerce kayıt içerdiğinde, durumu 'Tamamlandı' olanları veya belirli bir müşteriye ait olanları filtrelerken yavaşlama yaşanmaması için ne yapılabilir?**
**Cevap/Çözüm:** Veritabanı sorgu performansını artırmak için **indeksleme (indexing)** yönteminin kullanılması gerektiği belirlendi. `is_emirleri` tablosunda, `WHERE` koşullarında sıkça kullanılan `genel_durum`, `musteri_id` ve `urun_id` gibi sütunlara `INDEX` eklendi. İndeksleme, veritabanının bu sütunlardaki verileri ararken tüm tabloyu taramak yerine, çok daha hızlı bir arama yapısına başvurmasını sağlayarak sorgu sürelerini dramatik bir şekilde düşürür.

---

### **Soru 5: `password_hash()` fonksiyonu, `md5()` veya `sha1()` gibi eski yöntemlere göre neden daha güvenlidir?**
**Cevap/Çözüm:** `md5()` ve `sha1()` gibi eski algoritmalar çok hızlı çalıştıkları için modern donanımlarla yapılan "kaba kuvvet (brute-force)" ve "gökkuşağı tablosu (rainbow table)" saldırılarına karşı savunmasızdır. `password_hash()` ise varsayılan olarak **Bcrypt** algoritmasını kullanır. Bcrypt, bilinçli olarak yavaş çalışacak şekilde tasarlanmıştır ve her şifre için rastgele bir "salt" (tuz) değeri üretir. Bu yavaşlık ve tuzlama, kaba kuvvet saldırılarını pratik olarak imkansız hale getirir ve aynı şifreye sahip kullanıcıların bile veritabanında farklı hash değerlerine sahip olmasını sağlar.

---

### **Soru 6: Yeni bir iş emri `is_emirleri` tablosuna eklendikten sonra, bu iş emrine ait operasyonları `is_emri_operasyonlari` tablosuna eklemek için gereken `is_emri_id` nasıl elde edilir?**
**Cevap/Çözüm:** `is_emirleri` tablosuna `INSERT` sorgusu `execute()` ile başarıyla çalıştırıldıktan hemen sonra, `$mysqli->insert_id` özelliği kullanıldı. Bu özellik, o veritabanı bağlantısında en son yapılan `INSERT` işlemi sonucunda oluşan `AUTO_INCREMENT` ID değerini döndürür. Bu elde edilen ID, "çocuk" tablo olan `is_emri_operasyonlari` tablosuna yeni kayıtlar eklenirken `is_emri_id` sütununa değer olarak atandı.

---

### **Soru 7: Bir kullanıcıyı silmeye çalışırken, o kullanıcı tarafından oluşturulmuş iş emirleri varsa veri bütünlüğü nasıl korunur?**
**Cevap/Çözüm:** Veritabanı seviyesinde `FOREIGN KEY` kısıtlamaları (`ON DELETE RESTRICT`) sayesinde, bağımlı kayıtları olan bir kullanıcının silinmesi zaten engellenmektedir. Ancak bu, kullanıcıya çirkin bir SQL hatası gösterir. Daha profesyonel bir çözüm olarak, uygulama katmanında (PHP içinde) silme işlemi yapılmadan önce bir kontrol sorgusu çalıştırıldı. `SELECT COUNT(*) FROM is_emirleri WHERE olusturan_kullanici_id = ?` sorgusu ile kullanıcının bağımlı kaydı olup olmadığı kontrol edildi. Eğer kayıt varsa, silme işlemi hiç yapılmadı ve kullanıcıya "Bu kullanıcıya ait iş emirleri olduğu için silinemez" gibi anlamlı bir uyarı mesajı gösterildi.

---

### **Soru 8: Formları Cross-Site Request Forgery (CSRF) saldırılarından korumak için hangi mekanizma uygulanabilir?**
**Cevap/Çözüm:** CSRF saldırılarını önlemek için "Anti-CSRF Token" mekanizması önerildi. Kullanıcı giriş yaptığında, sunucu tarafında rastgele ve benzersiz bir token üretilip `$_SESSION['csrf_token']` içinde saklanır. Silme veya güncelleme gibi kritik işlemler yapan formlar oluşturulurken, bu token formun içine gizli bir alan (`<input type="hidden">`) olarak eklenir. Form gönderildiğinde, sunucu `$_POST` ile gelen token ile `$_SESSION`'daki token'ı karşılaştırır. Eğer token'lar eşleşmezse veya POST'ta token yoksa, işlem reddedilir. Bu, isteğin gerçekten kendi sitemizden ve doğru kullanıcı oturumundan geldiğini doğrular.

---

### **Soru 9: Projedeki tüm sayfalarda (header, footer, nav-bar) tekrar eden HTML kodlarını yönetmenin en verimli yolu nedir?**
**Cevap/Çözüm:** Kod tekrarını önlemek ve bakımı kolaylaştırmak için **DRY (Don't Repeat Yourself - Kendini Tekrar Etme)** prensibi uygulandı. Tekrar eden HTML bölümleri `includes/header.php` ve `includes/footer.php` gibi ayrı dosyalara taşındı. Ana sayfalarda (`index.php`, `is_emri_ekle.php` vb.), sayfanın en başında `require_once 'includes/header.php';` ve en sonunda `require_once 'includes/footer.php';` komutları kullanılarak bu parçalar dinamik olarak birleştirildi. Bu sayede, menüde yapılacak tek bir değişiklik tüm sayfalara anında yansıtılmış oldu.

---

### **Soru 10: Otomatik oluşturulan iş emri numarası (`IE-20250613-001`) için bir sonraki sıralı numara (`002`) nasıl güvenilir bir şekilde hesaplanır?**
**Cevap/Çözüm:** Yeni bir numara üretmeden önce, veritabanına o gün için en son eklenen kaydı bulan bir sorgu çalıştırıldı: `SELECT MAX(is_emri_no) FROM is_emirleri WHERE is_emri_no LIKE 'IE-20250613-%'`. Eğer bir sonuç bulunursa, bu sonucun sonundaki sayı (`-` karakterinden sonrası) alınıp 1 artırıldı. Eğer o gün için hiç kayıt yoksa, numara 1'den başlatıldı. Son olarak, elde edilen sayı `str_pad()` fonksiyonu kullanılarak, toplamda 3 karakter olacak şekilde başına '0' eklenerek formatlandı (Örn: `str_pad(2, 3, '0', STR_PAD_LEFT)` -> `'002'`). Bu yöntem, aynı anda birden fazla kullanıcı olsa bile çakışma riskini en aza indirir.
