# RivalRegions Benzeri Oyun - Görev Tablosu

> Durum notasyonu:
> - [ ] Bekliyor
> - [x] Tamamlandı (üzeri çizili)

1.) Görev: ~~Temel ürün planı ve kapsam dondurma (MVP + V2 + V3)~~ ✅

## 1. Görev Çıktısı (Tamamlandı)

### 1.1 Ürün vizyonu
- Tek oyunculu scaffold'dan çok oyunculu, ülke bazlı siyaset/savaş/ekonomi simülasyonuna geçiş.
- Web-first + API-first yaklaşımı: mobil istemci gelecekte aynı backend'i kullanacak.
- Oyun döngüsü: **Kayıt → Ulus/şehir yerleşimi → Çalışma/üretim → Market → Parti/siyaset → Savaş/kanun**.

### 1.2 Kapsam dondurma

#### MVP (Sürüm 0.1 - Oynanabilir Çekirdek)
- Üye kayıt/giriş, ülke-şehir atama.
- Temel statlar: enerji, XP, level, kuvvet/eğitim/dayanıklılık.
- Çalışma/savaş aksiyonları ve enerji rejenerasyonu.
- Kaynak envanteri + markette ilan açma/satın alma.
- Dünya haritasında şehir bazlı oyuncu yoğunluğu.
- Admin: ülke/şehir/kaynak dağılımı yönetimi.
- Operasyonel gereklilik: güvenlik kontrolleri (CSRF, auth guard, doğrulama).

#### V2 (Sürüm 0.2 - Siyasi Çekirdek)
- Parti kurma, üyelik sistemi.
- Rejim türleri: monarşi/diktatörlük/cumhuriyet.
- Aylık seçim takvimi (cron).
- Meclis kanun sistemi (oylama, savaş kanunu, ekonomik düzenlemeler).
- Bakanlık rollerinin işlevsel hale getirilmesi.

#### V3 (Sürüm 0.3 - Endüstri + Derin Savaş)
- Fabrika sistemi (kurulum, seviye, işçi, kapasite).
- Gelişmiş savaş: cephe/silah/lojistik.
- Şehir bina upgrade queue (havaalanı, liman, sanayi, uzay vb.).
- Ülke arası oturum/geçiş izin süreçleri.
- Anti-cheat, denge araçları, sezon/sıralama.

### 1.3 Modül bazlı sınırlar (Domain Boundaries)
- **Auth & Identity**: kullanıcı, oturum, güvenlik.
- **World**: ülke, şehir, nüfus ve harita.
- **Economy**: kaynaklar, üretim, stok, fiyat.
- **Market**: C2C ticaret, komisyon, transfer.
- **Politics**: parti, seçim, meclis, roller.
- **War**: savaş başlatma, hesaplama, sonuç raporu.
- **Admin**: world builder ve canlı denetim paneli.

### 1.4 Teknik prensipler (bu projede sabitlendi)
- PHP 8.3 + MariaDB (PDO) + Plesk/Apache uyumu korunacak.
- API sözleşmeleri JSON standardı ile versiyonlanacak (`/api/v1/...` geçiş planı).
- Büyük işlemler transaction ile korunacak.
- Zaman bazlı işlemler cron/event tabanlı çalışacak.
- Her kritik oyun aksiyonu audit log bırakacak.

### 1.5 Kabul kriterleri (Task-1 Definition of Done)
- [x] MVP, V2, V3 kapsamı yazılı hale getirildi.
- [x] Her sürüm için net modül sınırları belirlendi.
- [x] Teknik kısıtlar ve uyumluluk koşulları donduruldu.
- [x] Sonraki görevler için öncelik sırası sabitlendi.

---

2.) Görev: Veritabanı mimarisini production seviyesinde revize et
3.) Görev: Auth sistemi hardening (rate-limit, reset, session güvenliği)
4.) Görev: IP ülke atama + fallback/caching stratejisi
5.) Görev: Ulus/şehir domain kuralları (stat formülleri)
6.) Görev: Enerji/XP/Level/Stat formül motoru iyileştirme
7.) Görev: Kaynak sistemi tam sürüm + dağılım balansı
8.) Görev: Fabrika sistemi (kurulum/seviye/işçi)
9.) Görev: Market tam sürüm (vergi, komisyon, korumalar)
10.) Görev: Savaş sistemi çekirdeği + raporlama
11.) Görev: Parti/Seçim/Meclis sistemleri
12.) Görev: Bakanlık rolleri ve yetki akışları
13.) Görev: Oturum/geçiş izin sistemi
14.) Görev: Harita modülü genişletme + world builder
15.) Görev: Test, izleme, canlıya çıkış planı

---

**Sonraki adım önerisi:** 2. göreve geçelim ve şemayı migration mantığına göre normalize edelim.
