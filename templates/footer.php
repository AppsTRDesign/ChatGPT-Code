    </div>
    <footer class="footer mt-auto py-4 bg-dark text-white">
        <div class="container text-center small">
            <?= $settings['footer_html'] ?? '<p>© ' . date('Y') . ' NoaSoft</p>' ?>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/dropzone-lite.js?v=1.0.0"></script>
    <script src="<?= BASE_URL ?>/assets/js/app.js?v=1.1.0"></script>
    <?php
    global $pageScripts;
    if (!empty($pageScripts) && is_array($pageScripts)) {
        foreach ($pageScripts as $scriptTag) {
            echo $scriptTag;
        }
    }
    ?>
</body>
</html>
