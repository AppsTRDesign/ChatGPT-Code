<section class="container section">
  <h2><?= t('front', 'pricing') ?></h2>
  <p class="subtext"><?= t('front', 'pricing_desc') ?></p>
  <form id="pricingForm" class="panel">
    <label><?= t('front', 'country') ?></label>
    <select name="country_id" id="countrySelect" required>
      <option value="">--</option>
      <?php
      try {
          $stmt = db()->prepare('SELECT c.id, COALESCE(ct.name, c.name) AS name FROM countries c LEFT JOIN country_translations ct ON ct.country_id = c.id AND ct.lang_code = :lang WHERE c.is_active = 1 ORDER BY name');
          $stmt->execute(['lang' => $lang]);
          $countries = $stmt->fetchAll();
      } catch (Throwable $e) {
          $countries = [];
      }
      foreach ($countries as $country): ?>
        <option value="<?= (int) $country['id'] ?>"><?= htmlspecialchars($country['name'], ENT_QUOTES) ?></option>
      <?php endforeach; ?>
    </select>
    <label><?= t('front', 'category') ?></label>
    <select name="category_id" id="categorySelect" required></select>
    <label>Taşıma Seçeneği</label>
    <select name="transport_mode_id" id="transportModeSelect" required>
      <option value="">--</option>
      <?php
      try {
          $tmRows = db()->query('SELECT id,title FROM transport_modes WHERE is_active=1 ORDER BY id')->fetchAll();
      } catch (Throwable $e) {
          $tmRows = [];
      }
      foreach ($tmRows as $tm): ?>
        <option value="<?= (int) $tm['id'] ?>"><?= htmlspecialchars($tm['title'], ENT_QUOTES) ?></option>
      <?php endforeach; ?>
    </select>
    <label>En (cm)</label><input type="number" step="0.01" name="en" required>
    <label>Boy (cm)</label><input type="number" step="0.01" name="boy" required>
    <label>Yükseklik (cm)</label><input type="number" step="0.01" name="yukseklik" required>
    <label>Ağırlık (kg)</label><input type="number" step="0.01" name="kilo" required>
    <button type="submit"><?= t('front', 'calculate') ?></button>
  </form>
  <div id="pricingResult"></div>
</section>
