<?php
$cfg = settings();
$ymKey = $cfg['yandex_api_key'] ?? '';
$prefill = trim((string) ($_GET['num'] ?? ''));
?>
<section class="container section">
  <h2><?= t('front', 'track') ?></h2>
  <p class="subtext"><?= t('front', 'track_desc') ?></p>
  <form id="trackingForm" class="panel">
    <label><?= t('front', 'tracking_number') ?></label>
    <input type="text" name="tracking_number" value="<?= htmlspecialchars($prefill, ENT_QUOTES) ?>" required>
    <label><?= t('front', 'captcha') ?>: <strong><?= htmlspecialchars($_SESSION['captcha_text'] = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, CAPTCHA_LENGTH), ENT_QUOTES) ?></strong></label>
    <input type="text" name="captcha" required>
    <button type="submit"><?= t('front', 'track') ?></button>
  </form>

  <div id="trackingEmpty" class="panel"><p><?= t('front', 'tracking_waiting') ?></p></div>

  <div id="trackingDetail" class="grid-2" style="display:none">
    <div class="panel" id="trackingInfo"></div>
    <div class="panel"><div id="trackingMap" style="height:420px"></div><?php if (empty($ymKey)): ?><p class="subtext">Yandex API key ayarlanmadığı için harita gösterilemiyor.</p><?php endif; ?></div>
  </div>
</section>
<?php $ymLang = yandex_locale(); ?>
<?php if (!empty($ymKey)): ?>
<script src="https://api-maps.yandex.ru/v3/?apikey=<?= htmlspecialchars($ymKey, ENT_QUOTES) ?>&lang=<?= htmlspecialchars($ymLang, ENT_QUOTES) ?>"></script>
<?php endif; ?>

<script>window.TRACK_LABELS = {
  status: '<?= addslashes(t('front','status_label')) ?>',
  current_location: '<?= addslashes(t('front','current_location')) ?>',
  route: '<?= addslashes(t('front','route')) ?>',
  sender: '<?= addslashes(t('front','sender')) ?>',
  receiver: '<?= addslashes(t('front','receiver')) ?>',
  timeline: '<?= addslashes(t('front','timeline')) ?>',
  description: '<?= addslashes(t('front','description')) ?>',
  no_event: '<?= addslashes(t('front','no_event')) ?>'
};</script>
