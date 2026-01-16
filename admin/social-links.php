<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$links = db()->query('SELECT * FROM social_links ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$iconOptions = [
    'fa-brands fa-facebook-f',
    'fa-brands fa-instagram',
    'fa-brands fa-x-twitter',
    'fa-brands fa-tiktok',
    'fa-brands fa-youtube',
    'fa-brands fa-whatsapp',
    'fa-brands fa-telegram',
    'fa-brands fa-linkedin-in',
    'fa-brands fa-pinterest-p',
    'fa-brands fa-snapchat',
    'fa-brands fa-discord',
    'fa-brands fa-reddit-alien',
    'fa-brands fa-threads',
    'fa-brands fa-vk',
    'fa-brands fa-dribbble',
    'fa-brands fa-behance',
    'fa-brands fa-medium',
    'fa-brands fa-skype',
    'fa-brands fa-twitch',
    'fa-brands fa-slack',
    'fa-brands fa-spotify',
    'fa-brands fa-soundcloud',
    'fa-brands fa-github',
    'fa-brands fa-gitlab',
    'fa-brands fa-bitbucket',
    'fa-brands fa-google',
    'fa-brands fa-apple',
    'fa-brands fa-telegram',
    'fa-brands fa-whatsapp',
    'fa-brands fa-weixin',
    'fa-brands fa-weibo',
    'fa-brands fa-line',
    'fa-brands fa-kickstarter-k',
    'fa-brands fa-etsy',
    'fa-brands fa-paypal',
    'fa-brands fa-shopify',
];

admin_header('Sosyal Medya');
?>
<section class="panel">
    <form class="admin-form" data-ajax="social-link" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="0">
        <label>Buton İsmi
            <input type="text" name="label" placeholder="Örn: Instagram" required>
        </label>
        <label>Bağlantı
            <input type="url" name="url" placeholder="https://..." required>
        </label>
        <label>Sosyal Medya İkonu
            <div class="icon-picker">
                <input type="text" name="icon_class" placeholder="fa-brands fa-instagram" required>
                <button class="btn" type="button" data-icon-picker-open>İkon Seç</button>
            </div>
        </label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<section class="panel">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>İkon</th>
                <th>Ad</th>
                <th>Link</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($links as $link): ?>
                <tr>
                    <td><?= (int) $link['id'] ?></td>
                    <td><i class="<?= htmlspecialchars($link['icon_class']) ?>"></i></td>
                    <td><?= htmlspecialchars($link['label']) ?></td>
                    <td><?= htmlspecialchars($link['url']) ?></td>
                    <td>
                        <button
                            class="btn"
                            type="button"
                            data-social-edit
                            data-id="<?= (int) $link['id'] ?>"
                            data-label="<?= htmlspecialchars($link['label']) ?>"
                            data-url="<?= htmlspecialchars($link['url']) ?>"
                            data-icon="<?= htmlspecialchars($link['icon_class']) ?>"
                        >
                            Düzenle
                        </button>
                        <button class="btn danger" type="button" data-social-delete="<?= (int) $link['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<div class="modal" id="iconPickerModal" aria-hidden="true">
    <div class="modal-content">
        <button class="modal-close" type="button" data-modal-close aria-label="Kapat">×</button>
        <h3>İkon Seç</h3>
        <div class="icon-grid">
            <?php foreach ($iconOptions as $icon): ?>
                <button class="icon-option" type="button" data-icon-option="<?= htmlspecialchars($icon) ?>">
                    <i class="<?= htmlspecialchars($icon) ?>"></i>
                    <span><?= htmlspecialchars($icon) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
admin_footer();
?>
