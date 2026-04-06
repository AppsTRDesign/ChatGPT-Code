# Phase 2 TODO List (Detaylı)

## Epic-1: Realtime War Room 2.0
1. Authoritative savaş tick engine (server-side damage hesap)
2. Damage bar + live timeline + room replay
3. Savaş süresi bitişinde deterministik kazanan hesaplama
4. Toprak/eyalet transferinin otomatik uygulanması
5. Realtime savaş odası performans metrikleri

## Epic-2: Coup / Uprising / New State
6. Darbe fonlama havuzu + katkı limitleri
7. Darbe/ayaklanma state machine (funding->active->won/lost)
8. Darbe kazanımı sonrası yeni devlet kurulumu
9. Kurucu ekip içi seçim ve hükümet ataması
10. 3 günlük savaş koruma kuralı uygulaması

## Epic-3: Statecraft & Province Diplomacy
11. Eyalet bağışı mekanizması (rejime göre farklı yetki)
12. Cumhuriyette meclis onayı, diktatörlükte tek imza
13. Eyalet başkanı/owner değişim logları
14. Eyalet adı/renk/bayrak güncelleme akışı
15. Yeni ülkelerin sıralamaya anlık dahil edilmesi

## Epic-4: Asset & Flag Pipeline
16. Bayrak yükleme servisi (jpg/png/webp/gif doğrulama)
17. Dosya boyutu/MIME güvenlik kontrolleri
18. Asset versioning ve CDN cache stratejisi
19. Yetki ve audit log entegrasyonu

## Epic-5: Politics Deepening
20. Savaş yasası teklif tipleri ve etkileri
21. Meclis quorum/veto/emergency prosedürleri
22. Aylık seçim cron akışları + kapanış etkileri

## Epic-6: Economy & Factory Chain 2.0
23. Çok adımlı fabrika zinciri (input->intermediate->output)
24. Şehir bina upgrade queue sistemi
25. Oturum/vize tiplerini derinleştirme
26. Ekonomi anti-cheat anomali tespit dashboard’u

## Epic-7: API / Frontend / Mobile
27. API v2 sözleşmesi + deprecation planı
28. Full desktop/mobile UX bileşenleri
29. Çok sayfalı modüler game/admin navigasyonu
30. Socket event contract dökümü

## Epic-8: QA / Load / Operations
31. Unit+integration kapsam genişletme
32. Load/soak test senaryoları (war/market/socket)
33. Canary rollout + rollback runbook
34. Incident response playbook
35. SLO/SLA ve alarm setleri
