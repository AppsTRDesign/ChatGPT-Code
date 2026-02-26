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
</section>
