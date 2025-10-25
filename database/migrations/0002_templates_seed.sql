INSERT INTO templates (client_id, name, slug, content)
VALUES
    (NULL, 'Kampanya Tanıtımı', 'template-01', '<div class="notification-template template-01">...</div>'),
    (NULL, 'Canlı Yayın Duyurusu', 'template-02', '<div class="notification-template template-02">...</div>'),
    (NULL, 'Sepet Hatırlatma', 'template-03', '<div class="notification-template template-03">...</div>'),
    (NULL, 'Sadakat Programı', 'template-04', '<div class="notification-template template-04">...</div>'),
    (NULL, 'Uygulama Güncelleme', 'template-05', '<div class="notification-template template-05">...</div>'),
    (NULL, 'Lokasyon Fırsatı', 'template-06', '<div class="notification-template template-06">...</div>'),
    (NULL, 'Video Tanıtımı', 'template-07', '<div class="notification-template template-07">...</div>'),
    (NULL, 'Etkinlik Hatırlatma', 'template-08', '<div class="notification-template template-08">...</div>'),
    (NULL, 'Stok Uyarısı', 'template-09', '<div class="notification-template template-09">...</div>'),
    (NULL, 'Anlık Kampanya', 'template-10', '<div class="notification-template template-10">...</div>')
ON DUPLICATE KEY UPDATE content = VALUES(content), name = VALUES(name);
