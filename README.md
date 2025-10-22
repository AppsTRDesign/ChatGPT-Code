# NoaSoft Converter

## English

### Overview
NoaSoft Converter is a responsive PHP 8.2 web application for converting audio, video, and image files directly in the browser. It targets a Plesk-managed AlmaLinux 8 host with FFmpeg 4.2+, delivering an AJAX-driven workflow with SweetAlert2 notifications, Dropzone-powered uploads, and configurable presets tuned for popular social platforms.

### Key Features
- Unified landing page describing the service, how-to steps, and quick links to each conversion tool.
- Dedicated converters for audio, video, and image processing with per-category validation so only compatible formats can be uploaded.
- Drag & drop or click-to-select uploads powered by Dropzone, with custom styling, file count badges, size checks, and live file metadata.
- Sequential job pipeline that tracks each file through upload, conversion, and download stages while rendering distinct progress bars and percentages.
- SweetAlert2 messaging for every validation, warning, or success state (no native browser alerts are used).
- Cancelable jobs that stop uploads, conversion, and downloads immediately, cleaning up any temporary or output artifacts on the server.
- Preset management for audio, video, and image outputs. Selecting a preset auto-fills and locks FFmpeg/GD options, while "custom" re-enables manual control.
- SEO-friendly routing via `.htaccess` rewrites so public URLs map to `/audio-convert`, `/video-convert`, `/image-convert`, `/faq`, `/contact`, and `/copyright`.
- Config-driven limits that centralize upload caps, mail settings, base URLs, and navigation links in `includes/config.php`.
- Contact form with PHP mail support. SMTP can be toggled from the configuration file if external delivery is required.

### Technology Stack
| Layer | Technology |
| --- | --- |
| Language | PHP 8.2 |
| Media Processing | FFmpeg 4.2+ for audio/video, GD/ImageMagick (via PHP) for image tweaks |
| Front-end Libraries | Dropzone 5.9.3, SweetAlert2, Alpine.js 3.13.7 |
| Styling | Custom responsive CSS (`assets/css/style.css`) built on the provided blue/black theme |
| JavaScript | Modular helper (`assets/js/converter.js`) orchestrating uploads, polling, and preset logic |
| Routing | Apache `.htaccess` rewrites |

### Project Structure
```
├── assets/
│   ├── css/style.css          # Global theme with mobile-first form and Dropzone styles
│   └── js/
│       ├── converter.js       # Core converter logic (Dropzone setup, progress, presets)
│       └── main.js            # Navigation toggles and shared UI helpers
├── ajax/
│   ├── process.php            # Handles uploads, invokes FFmpeg/GD, and streams progress updates
│   ├── status.php             # Reports job status, percentages, and next actions
│   ├── cancel.php             # Cancels active jobs and purges temporary files
│   └── contact.php            # Processes contact form submissions using config-driven mail settings
├── includes/
│   ├── config.php             # Central configuration for site metadata, limits, and SMTP
│   ├── functions.php          # Helper utilities, job lifecycle management, routing helpers
│   ├── header.php / footer.php# Shared layout scaffolding and asset loading
├── storage/                   # Uploads, outputs, and job metadata (auto-created)
├── index.php                  # Landing page with feature overview and CTA links
├── audio.php                  # Audio converter view
├── video.php                  # Video converter view with presets
├── image.php                  # Image converter view with presets
├── faq.php                    # Frequently asked questions content
├── contact.php                # Contact form page using SweetAlert feedback
├── copyright.php              # Legal / copyright notice
├── download.php               # Secure download endpoint with progress feedback
└── .htaccess                  # SEO rewrites and caching headers
```

### Configuration
1. Copy `includes/config.php` and adjust values as needed:
   - `site.base_url` – set if the application lives in a subdirectory.
   - `upload.max_files` and `upload.max_size_mb` – enforce the maximum files per batch and per-file size.
   - `mail` block – configure the destination address and optional SMTP credentials. When `smtp.enabled` is `true`, the script uses SMTP parameters if provided, otherwise PHP's `mail()` is used.
2. Ensure the `storage/` directory (and its subfolders) are writable by the web server. The application auto-creates `uploads`, `output`, and `jobs` folders on demand.
3. Update `includes/config.php` any time branding, navigation, or limits need to change—no code edits elsewhere are required.

### Installation & Deployment
1. **Server requirements:** PHP 8.2 with `proc_open`, `exec`, and `shell_exec` enabled; FFmpeg 4.2+ installed and available in `$PATH`; GD or Imagick for image adjustments.
2. **Clone or upload** the repository to the `game.noasoft.org` root (or desired virtual host).
3. **Set permissions** so that `storage/` is writable (e.g., `chmod -R 775 storage`).
4. **Configure Apache/Nginx:** For Apache, the provided `.htaccess` handles rewrites automatically. For Nginx or Plesk, translate the rewrite rules to the respective configuration.
5. **Verify PHP mail:** If SMTP is required, fill in the credentials in `config.php`; otherwise, ensure the server's sendmail transport is active.
6. **Visit the site** and test each converter (audio/video/image) to confirm FFmpeg binaries are detected and progress bars advance as expected.

### Conversion Workflow
1. Users drag or click to add up to five files. Dropzone filters the allowed extensions per converter (audio/video/image) and shows live file chips with size data.
2. Pressing the convert button starts a staged progress UI:
   - **Upload stage:** Each file uploads sequentially with a dedicated progress bar and status label.
   - **Conversion stage:** Once uploaded, FFmpeg or GD processes the file. Output percentages are streamed back through AJAX polling.
   - **Download stage:** When conversion completes, the download button activates. Clicking it shows a download progress bar before streaming the file.
3. Cancelling at any stage stops the active request, marks the job as cancelled, purges source/output artifacts, and resets the interface.
4. Successful downloads trigger automatic cleanup of temporary files and restore the form to its initial state.

### SEO-Friendly Routes
The `.htaccess` file maps human-readable slugs to their PHP counterparts:
- `/audio-convert` → `audio.php`
- `/video-convert` → `video.php`
- `/image-convert` → `image.php`
- `/faq` → `faq.php`
- `/contact` → `contact.php`
- `/copyright` → `copyright.php`

### JavaScript & Styling Notes
- `assets/js/converter.js` exposes an `nsInitializeConverter` helper that accepts a configuration object from each converter page. It wires Dropzone, SweetAlert2, the multi-stage progress bars, cancellation logic, and preset synchronization.
- The application loads Alpine.js (deferred) for lightweight interactivity and Dropzone 5.9.3 for drag-and-drop uploads via CDN, satisfying the user's asset requirements.
- `assets/css/style.css` implements the supplied design tokens (blue/black palette) with responsive forms, stacked grid layouts under 900px, and compact Dropzone previews that gracefully truncate long filenames.

### Contact & Notifications
- The contact form posts to `ajax/contact.php`, which uses the config-driven `mail` settings.
- All user-facing feedback—errors, warnings, and confirmations—are surfaced through SweetAlert2 modals, ensuring consistent styling across devices.

### Maintenance Tips
- Periodically clear the `storage/` directory if large files accumulate, though the application cleans up after each job.
- To introduce new presets, edit the preset arrays in `audio.php`, `video.php`, or `image.php` and mirror any server-side expectations in `ajax/process.php`.
- Before deploying updates, run `php -l` or your preferred static analysis on the PHP files to catch syntax issues.

### Testing
A quick syntax check across all PHP scripts:
```bash
find . -name "*.php" -print0 | xargs -0 -n1 php -l
```
For end-to-end verification, upload sample media files (within the configured size limit) to each converter and confirm that uploads, conversions, downloads, and cancellations behave as expected.

---

## Türkçe

### Genel Bakış
NoaSoft Converter, ses, video ve görsel dosyalarını doğrudan tarayıcı üzerinden dönüştürmek için geliştirilmiş, duyarlı tasarıma sahip bir PHP 8.2 web uygulamasıdır. FFmpeg 4.2+ kurulu Plesk yönetimli AlmaLinux 8 sunucularını hedefler; SweetAlert2 bildirimleri, Dropzone tabanlı yüklemeler ve popüler sosyal platformlara yönelik özelleştirilebilir preset seçenekleri ile tamamen AJAX tabanlı bir iş akışı sunar.

### Öne Çıkan Özellikler
- Hizmeti, kullanım adımlarını ve her dönüştürücüye giden kısayolları anlatan birleşik bir ana sayfa.
- Yalnızca uyumlu formatların yüklenmesini sağlayan kategori bazlı doğrulamalara sahip ses, video ve görsel dönüştürücüler.
- Dropzone tarafından desteklenen sürükle-bırak veya tıklayarak dosya seçimi; özel tema, dosya sayısı rozetleri, boyut kontrolleri ve canlı dosya metaverisi.
- Yükleme, dönüştürme ve indirme aşamalarını ayrı ilerleme çubuklarıyla takip eden sıralı görev hattı.
- Tüm doğrulama, uyarı ve başarı durumları için SweetAlert2 mesajları (yerleşik tarayıcı uyarıları kullanılmaz).
- Sunucudaki geçici veya çıktı dosyalarını anında temizleyen iptal edilebilir işler.
- Ses, video ve görseller için preset yönetimi. Bir preset seçildiğinde FFmpeg/GD seçenekleri otomatik doldurulur ve kilitlenir; "özel" seçildiğinde manuel kontrol yeniden açılır.
- `.htaccess` yönlendirmeleri sayesinde `/audio-convert`, `/video-convert`, `/image-convert`, `/faq`, `/contact` ve `/copyright` gibi SEO dostu URL'ler.
- Yükleme limitleri, mail ayarları, temel adres ve gezinme bağlantılarını `includes/config.php` içinde toplayan yapılandırma.
- PHP mail desteğine sahip iletişim formu. Gerekirse yapılandırmadan SMTP etkinleştirilebilir.

### Teknoloji Yığını
| Katman | Teknoloji |
| --- | --- |
| Dil | PHP 8.2 |
| Medya İşleme | Ses/video için FFmpeg 4.2+, görsel düzenlemeleri için PHP üzerinden GD/ImageMagick |
| Ön Yüz Kütüphaneleri | Dropzone 5.9.3, SweetAlert2, Alpine.js 3.13.7 |
| Stil | Sağlanan mavi/siyah temayı kullanan özel duyarlı CSS (`assets/css/style.css`) |
| JavaScript | Yükleme, sorgulama ve preset mantığını yöneten modüler yardımcı (`assets/js/converter.js`) |
| Yönlendirme | Apache `.htaccess` rewrite kuralları |

### Proje Yapısı
```
├── assets/
│   ├── css/style.css          # Mobil öncelikli form ve Dropzone stilleri sunan tema
│   └── js/
│       ├── converter.js       # Dropzone kurulumu, ilerleme ve preset kontrolü
│       └── main.js            # Menü geçişleri ve ortak arayüz yardımcıları
├── ajax/
│   ├── process.php            # Yüklemeleri işler, FFmpeg/GD çalıştırır ve ilerleme gönderir
│   ├── status.php             # Görev durumunu, yüzdeleri ve sonraki adımları bildirir
│   ├── cancel.php             # Aktif işleri iptal eder ve geçici dosyaları temizler
│   └── contact.php            # Yapılandırma tabanlı mail ayarlarıyla iletişim formlarını işler
├── includes/
│   ├── config.php             # Site bilgileri, limitler ve SMTP için merkezi yapılandırma
│   ├── functions.php          # Yardımcı fonksiyonlar, görev yaşam döngüsü ve yönlendirme
│   ├── header.php / footer.php# Ortak şablon ve varlık yüklemeleri
├── storage/                   # Yükleme, çıktı ve görev metaverileri (otomatik oluşturulur)
├── index.php                  # Özellik özetli ve CTA bağlantılı ana sayfa
├── audio.php                  # Ses dönüştürücü arayüzü
├── video.php                  # Preset destekli video dönüştürücü
├── image.php                  # Preset destekli görsel dönüştürücü
├── faq.php                    # Sıkça sorulan sorular sayfası
├── contact.php                # SweetAlert geri bildirimli iletişim formu
├── copyright.php              # Telif ve yasal bilgilendirme
├── download.php               # İndirme ilerlemesi sunan güvenli uç nokta
└── .htaccess                  # SEO uyumlu yönlendirmeler
```

### Yapılandırma
1. `includes/config.php` dosyasını kopyalayıp ihtiyaca göre düzenleyin:
   - `site.base_url` – Uygulama bir alt klasörde çalışıyorsa belirtin.
   - `upload.max_files` ve `upload.max_size_mb` – Yükleme başına dosya sayısı ve tek dosya boyutunu sınırlar.
   - `mail` bölümü – Hedef adresi ve isteğe bağlı SMTP bilgilerini girin. `smtp.enabled` değeri `true` olduğunda bilgiler doluysa SMTP, aksi halde PHP `mail()` fonksiyonu kullanılır.
2. Web sunucusunun `storage/` klasörüne (ve alt klasörlerine) yazma izni olduğundan emin olun. Uygulama gerektiğinde `uploads`, `output` ve `jobs` klasörlerini kendi oluşturur.
3. Marka, gezinme veya limitler değiştiğinde `includes/config.php` dosyasını güncellemeniz yeterlidir; başka kod değişikliğine gerek yoktur.

### Kurulum ve Yayınlama
1. **Sunucu gereksinimleri:** `proc_open`, `exec` ve `shell_exec` fonksiyonları açık PHP 8.2; PATH içinde FFmpeg 4.2+; görseller için GD veya Imagick.
2. **Depoya** veya arzu edilen sanal host kök dizinine projeyi yükleyin (`game.noasoft.org`).
3. **İzinleri ayarlayın:** `storage/` klasörünün yazılabilir olduğundan emin olun (örn. `chmod -R 775 storage`).
4. **Apache/Nginx yapılandırması:** Apache için ek bir işlem gerekmez; `.htaccess` gerekli yönlendirmeleri yapar. Nginx veya Plesk kullanıyorsanız kuralları uygun biçimde uyarlayın.
5. **PHP mail doğrulaması:** SMTP kullanılacaksa `config.php` içindeki alanları doldurun; aksi hâlde sunucunun mail transfer aracının çalıştığından emin olun.
6. **Siteyi ziyaret ederek** ses/video/görsel dönüştürücüleri test edin; FFmpeg erişilebilir ve ilerleme çubukları sorunsuz hareket ediyor olmalıdır.

### Dönüştürme İş Akışı
1. Kullanıcılar en fazla beş dosyayı sürükleyerek veya tıklayarak ekler. Dropzone, seçilen dönüştürücü türüne göre (ses/video/görsel) izin verilen uzantıları süzer ve dosya boyutlarını gösterir.
2. Dönüştür butonuna basıldığında kademeli bir ilerleme arayüzü başlar:
   - **Yükleme aşaması:** Her dosya sırasıyla yüklenir; kendine ait ilerleme çubuğu ve durum etiketi vardır.
   - **Dönüştürme aşaması:** Yükleme bittiğinde FFmpeg veya GD işlemi yürütülür ve AJAX ile yüzdeler aktarılır.
   - **İndirme aşaması:** Dönüşüm tamamlandığında indirme butonu etkinleşir; tıklandığında indirme çubuğu gösterilir ve dosya akışı başlar.
3. Herhangi bir aşamada iptal edildiğinde aktif istek durdurulur, görev iptal olarak işaretlenir, kaynak/çıktı dosyaları silinir ve arayüz varsayılana döner.
4. Başarılı indirmeler otomatik temizlik yapar ve formu ilk hâline getirir.

### SEO Dostu Bağlantılar
`.htaccess` dosyası kullanıcı dostu adresleri ilgili PHP dosyalarına yönlendirir:
- `/audio-convert` → `audio.php`
- `/video-convert` → `video.php`
- `/image-convert` → `image.php`
- `/faq` → `faq.php`
- `/contact` → `contact.php`
- `/copyright` → `copyright.php`

### JavaScript ve Stil Notları
- `assets/js/converter.js`, her dönüştürücü sayfanın kendi yapılandırmasıyla çalışan `nsInitializeConverter` yardımcı fonksiyonunu sunar; Dropzone, SweetAlert2, çok aşamalı ilerleme çubukları, iptal mantığı ve preset senkronizasyonu buradan yönetilir.
- Uygulama, hafif etkileşimler için Alpine.js'i (deferred) ve sürükle-bırak yüklemeler için Dropzone 5.9.3'ü CDN üzerinden yükler; bu yapı kullanıcının istekleriyle uyumludur.
- `assets/css/style.css`, sağlanan mavi/siyah tasarım değerlerini kullanarak duyarlı formlar, 900px altında dikey yığınlanan gridler ve uzun dosya adlarını zarifçe kısaltan Dropzone önizlemeleri sunar.

### İletişim ve Bildirimler
- İletişim formu `ajax/contact.php` adresine POST eder ve yapılandırmadaki `mail` ayarlarını kullanır.
- Tüm kullanıcı geri bildirimleri—hata, uyarı ve başarı mesajları—SweetAlert2 modallarıyla gösterilerek cihazlar arası tutarlılık sağlanır.

### Bakım İpuçları
- Dönüştürme sonrası dosyalar otomatik silinse de `storage/` klasörünü periyodik olarak kontrol edin.
- Yeni preset eklemek için `audio.php`, `video.php` veya `image.php` dosyalarındaki preset dizilerini güncelleyin ve `ajax/process.php` içinde karşılık gelen server-side beklentileri düzenleyin.
- Güncelleme yapmadan önce PHP dosyalarını `php -l` veya tercih ettiğiniz analiz araçlarıyla hızlıca kontrol edin.

### Test
Tüm PHP betiklerinde hızlı sözdizimi kontrolü:
```bash
find . -name "*.php" -print0 | xargs -0 -n1 php -l
```
Uçtan uca doğrulama için, her dönüştürücüde limitlere uygun örnek medya dosyaları yükleyerek yükleme, dönüştürme, indirme ve iptal senaryolarını test edin.
