<section class="container section">
  <h2><?= t('front', 'pricing') ?></h2>
  <form id="pricingForm" class="panel">
    <label><?= t('front', 'country') ?></label>
    <select name="country_id" id="countrySelect" required>
      <option value="">--</option>
      <?php
      try {
          $countries = db()->query('SELECT id, name FROM countries WHERE is_active = 1 ORDER BY name')->fetchAll();
      } catch (Throwable $e) {
          $countries = [];
      }
      foreach ($countries as $country): ?>
        <option value="<?= (int) $country['id'] ?>"><?= htmlspecialchars($country['name'], ENT_QUOTES) ?></option>
      <?php endforeach; ?>
    </select>
    <label><?= t('front', 'category') ?></label>
    <select name="category_id" id="categorySelect" required></select>
    <button type="submit"><?= t('front', 'calculate') ?></button>
  </form>
  <div id="pricingResult"></div>
</section>
