    </div>
    <footer class="footer mt-auto py-4 bg-dark text-white">
        <div class="container text-center small">
            <?= $settings['footer_html'] ?? '<p>© ' . date('Y') . ' NoaSoft</p>' ?>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= BASE_URL ?>',
            csrfToken: '<?= csrf_token() ?>'
        };
    </script>
    <script src="<?= BASE_URL ?>/assets/js/app.js?v=1.0.0"></script>
    <?= $settings['footer_html'] ?? '' ?>
</body>
</html>
