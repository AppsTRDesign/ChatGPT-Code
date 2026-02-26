<?php $cfg = settings(); $ymKey = $cfg['yandex_api_key'] ?? 'd0b1a4c0-60eb-4a39-b34a-61c68fffc2d6'; ?>
<section class="container section">
  <h2><?= t('front', 'track') ?></h2>
  <p class="subtext"><?= t('front', 'track_desc') ?></p>
  <form id="trackingForm" class="panel">
    <label><?= t('front', 'tracking_number') ?></label>
    <input type="text" name="tracking_number" required>
    <label><?= t('front', 'captcha') ?>: <strong><?= htmlspecialchars($_SESSION['captcha_text'] = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, CAPTCHA_LENGTH), ENT_QUOTES) ?></strong></label>
    <input type="text" name="captcha" required>
    <button type="submit"><?= t('front', 'track') ?></button>
  </form>
  <div id="trackingResult"></div>
  <div class="panel"><div id="trackingMap" style="height:360px"></div></div>
</section>
<script src="https://api-maps.yandex.ru/2.1/?apikey=<?= htmlspecialchars($ymKey, ENT_QUOTES) ?>&lang=<?= htmlspecialchars($lang, ENT_QUOTES) ?>"></script>
