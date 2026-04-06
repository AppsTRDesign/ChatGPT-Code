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


## 3. Görev Çıktısı (Tamamlandı)
- Login/Register/Forgot için rate-limit katmanı eklendi (`auth_rate_limits`).
- Şifre sıfırlama token akışı eklendi (`password_reset_tokens`).
- Session hijack riskini azaltmak için login/logout anında `session_regenerate_id(true)` aktif edildi.
- Session strict-mode ve güvenlik başlıkları bootstrap'te aktif edildi.
- Forgot/reset ekranları ve route'ları eklendi.


## 4. Görev Çıktısı (Tamamlandı)
- GeoIP için çok sağlayıcılı çözüm eklendi (`ip-api` + `ipwho.is`).
- `geoip_cache` tablosu ile IP->ülke kodu cache katmanı eklendi.
- Provider başarısız olursa `Accept-Language` bazlı fallback çalışıyor.
- Private/reserved IP'lerde doğrudan fallback devreye giriyor.
- Ülkeye düşen kullanıcıların şehir yerleşimi yük dengeleme ile yapılıyor (az oyunculu şehre öncelik).


## 5. Görev Çıktısı (Tamamlandı)
- Stat formülleri ayrı servis katmanına taşındı (`StatFormulaService`).
- Ulus seviyesi (nation tier) oyuncu sayısı + ortalama şehir skoruna göre hesaplanıyor.
- Enerji yenilenmesi ulus seviyesi ve top şehir etkisine göre dinamik hale getirildi.
- Çalışma/savaş kazanımları ulus + statlara göre ölçekleniyor.
- Stat geliştirme maliyeti stat değerine göre artan maliyet modeline geçirildi.


## 6. Görev Çıktısı (Tamamlandı)
- Balance değerleri `settings` üzerinden yönetilir hale getirildi (`BalanceConfigService`).
- XP/enerji/level formülleri parametreli hale getirildi (`StatFormulaService`).
- Auto-levelup çoklu seviye geçiş destekli oldu.
- Dashboard'a ilerleme metriği (`next_level_xp`) eklendi.
- Balance tuning migration'ı eklendi (`20260405_000005_balance_tuning.sql`).


## 7. Görev Çıktısı (Tamamlandı)
- Ülke kaynakları için stok/yenilenme/kalite modeli eklendi.
- Kaynak fiyatları kıtlık ve kaliteye göre dinamik hesaplanır hale getirildi.
- Work aksiyonu ülke global stoklarını tüketiyor.
- Dashboard'a kaynak piyasa dengesi tablosu eklendi.
- Resource market snapshot API state içine entegre edildi.


## 8. Görev Çıktısı (Tamamlandı)
- Fabrika veri modeli (tip/fabrika/log) eklendi.
- Fabrika kurulum ve üretim akışları backend'e eklendi.
- Fabrika üretimi enerji ve hammadde kontrolü ile çalışır hale geldi.
- Dashboard'a fabrika yönetim paneli eklendi.
- API'ye fabrika create/produce endpointleri eklendi.


## 9. Görev Çıktısı (Tamamlandı)
- Market ilanlarına alıcı vergi + satıcı komisyon modeli eklendi.
- İlan açarken dinamik referans fiyata göre taban/tavan fiyat koruması eklendi.
- Oyuncu başına açık ilan limiti ve kendi ilanını satın alma engeli eklendi.
- Satın alma işlemleri transaction + `FOR UPDATE` ile yarış durumlarına karşı korundu.
- `market_transactions` işlem geçmişi tablosu eklendi ve dashboard market tablosu genişletildi.


## 10. Görev Çıktısı (Tamamlandı)
- `wars` ve `war_battles` tabloları ile savaş çekirdeği veri modeli eklendi.
- Savaş başlatma akışı eklendi (aktif savaş çakışma kontrolü + seviye/savaş gücü eşiği).
- Cephe saldırısı akışı eklendi (enerji maliyeti, cooldown, skor hasarı, savaş bitiş koşulu).
- Savaş saldırıları raporlanır hale geldi (`war_battles`) ve dashboard’da canlı listeye bağlandı.
- API’ye `POST /api/war/start` ve `POST /api/war/attack` endpointleri eklendi.


## 11. Görev Çıktısı (Tamamlandı)
- Parti sistemi aktif edildi (parti kurma/katılma/ayrılma).
- Seçim çekirdeği genişletildi (seçim açma, oy verme, seçim kapanışında lider rol atama).
- Meclis kanun teklif/oylama modeli eklendi (`parliament_laws`, `parliament_law_votes`).
- Dashboard’a siyaset merkezi eklendi (partiler, seçim ekranı, meclis kanun listesi).
- API’ye siyasi endpointler eklendi (`/api/party/*`, `/api/election/*`, `/api/law/*`).


## 12. Görev Çıktısı (Tamamlandı)
- Bakanlık yetki modeli eklendi (`government_role_permissions`).
- Bakanlık aksiyon log sistemi eklendi (`ministry_action_logs`).
- Başkan için rol atama akışı eklendi (`/api/gov/assign-role`).
- Ekonomi/Savunma bakanlık aksiyonları eklendi (`/api/gov/action`).
- Dashboard’a “Bakanlık Rolleri ve Yetki Akışları” paneli eklendi.


## 13. Görev Çıktısı (Tamamlandı)
- Oturum/geçiş izin veri modeli eklendi (`residence_permits`, `country_travel_policies`, `travel_logs`).
- Oyuncu geçiş izni talebi akışı eklendi (`/api/travel/request-permit`).
- İçişleri/Başkan izin karar akışı eklendi (`/api/travel/permit-decision`).
- Şehir/ülke değişimi ve vize kontrolü eklendi (`/api/travel/move`).
- Dashboard’a “Oturum / Geçiş İzin Sistemi” paneli eklendi.


## 14. Görev Çıktısı (Tamamlandı)
- Harita katman veri modeli eklendi (`world_map_layers`).
- Şehir POI veri modeli eklendi (`city_points_of_interest`).
- Map payload, katman ve POI destekleyecek şekilde genişletildi.
- Admin world builder’a harita katmanı ve POI yönetim formları eklendi.
- Admin panelde harita katmanları/POI listeleri görüntülenebilir hale geldi.


## 15. Görev Çıktısı (Tamamlandı)
- Oturum izni başvuru/onay/red/ihlal akışı genişletildi.
- Vatandaşlık başvuru ve karar akışı eklendi.
- İzin süresi (`valid_until`) ve ihlal alanları eklendi.
- Süre dolunca otomatik ülkeye dönüş için border event queue altyapısı eklendi.
- Dashboard’a vatandaşlık ve ihlal görünürlüğü eklendi.

## 16. Görev Çıktısı (Tamamlandı)
- Dünya haritası etkileşimli hale getirildi (ülke tıklama, tooltip, detay paneli).
- Haritaya katman seçici eklendi (`influence`, `population`, `resource_total_yield`).
- Şehir/POI marker filtreleme eklendi.
- Seçilen ülke için oyuncu/şehir/POI/üretim özeti eklendi.
- Harita lejantı aktif katmana göre dinamik güncellenir hale getirildi.

## 17. Görev Çıktısı (Tamamlandı)
- Admin World Builder artık yalnızca ekleme değil güncelleme/silme süreçlerini de kapsar hale getirildi.
- Ülke ve şehir güncelleme formları eklendi (aktif/pasif kontrolü dahil).
- Kaynak dağılımı, harita katmanı ve şehir POI kayıtlarına silme aksiyonları eklendi.
- World Builder JSON export endpointi eklendi.
- World Builder JSON import akışı eklendi (country/city/resource/layer/poi upsert).

---

2.) Görev: ~~Veritabanı mimarisini production seviyesinde revize et~~ ✅
3.) Görev: ~~Auth sistemi hardening (rate-limit, reset, session güvenliği)~~ ✅
4.) Görev: ~~IP ülke atama + fallback/caching stratejisi~~ ✅
5.) Görev: ~~Ulus/şehir domain kuralları (stat formülleri)~~ ✅
6.) Görev: ~~Enerji/XP/Level/Stat formül motoru iyileştirme~~ ✅
7.) Görev: ~~Kaynak sistemi tam sürüm + dağılım balansı~~ ✅
8.) Görev: ~~Fabrika sistemi (kurulum/seviye/işçi)~~ ✅
9.) Görev: ~~Market tam sürüm (vergi, komisyon, korumalar)~~ ✅
10.) Görev: ~~Savaş sistemi çekirdeği + raporlama~~ ✅
11.) Görev: ~~Parti/Seçim/Meclis sistemleri~~ ✅
12.) Görev: ~~Bakanlık rolleri ve yetki akışları~~ ✅
13.) Görev: ~~Oturum/geçiş izin sistemi~~ ✅
14.) Görev: ~~Harita modülü genişletme + world builder~~ ✅
15.) Görev: ~~Oturum izni / vatandaşlık / sınır geçiş sistemi~~ ✅
16.) Görev: ~~Dünya haritası modülü (interaktif)~~ ✅
17.) Görev: ~~Admin World Builder (ülke/şehir/kaynak ekleme paneli)~~ ✅
18.) Görev: Top şehir sistemi ve global sıralamalar
19.) Görev: Görevler, başarımlar, günlük görev sistemi
20.) Görev: Bildirim sistemi (toast + inbox + event feed)
21.) Görev: API v1 standardizasyonu (mobil hazır)
22.) Görev: Frontend UI/UX full mobil optimizasyon
23.) Görev: Ekonomi balans ve anti-cheat katmanı
24.) Görev: Test altyapısı (unit + integration + load)
26.) Görev: Gezgin Tüccar Sistemi (Node.js anlık toast bildirim)
27.) Görev: Node.js 16.20.2 + socket.io uyumlu anlık savaş mekaniği/bildirimler
28.) Görev: Canlıya çıkış ve operasyon planı

---

**Sonraki adım önerisi:** 18. göreve geçelim (Top şehir sistemi ve global sıralamalar).
