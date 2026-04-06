# Task-11: Parti / Seçim / Meclis Sistemleri

## Tamamlananlar

- Parti mekanikleri:
  - Parti kurma (`createParty`)
  - Parti katılımı (`joinParty`)
  - Partiden ayrılma (`leaveParty`)
- Seçim mekanikleri:
  - Açık seçim başlatma (`openElection`)
  - Seçimde oy kullanma (`voteElection`)
  - Süresi dolan seçimleri kapatma ve kazanan lideri ülke rolüne atama
- Meclis mekanikleri:
  - Kanun önerme (`proposeLaw`)
  - Kanun oylama (`voteLaw`)
  - Süresi dolan kanunları kabul/reddet olarak kapatma
- Dashboard siyaset merkezi:
  - Parti listesi ve üyelik işlemleri
  - Seçim ekranı ve oy verme
  - Meclis kanun listesi ve Evet/Hayır oyu

## Yeni API Endpointleri

- `POST /api/party/create`
- `POST /api/party/join`
- `POST /api/party/leave`
- `POST /api/election/open`
- `POST /api/election/vote`
- `POST /api/law/propose`
- `POST /api/law/vote`

## Yeni Ayarlar (settings)

- `election_default_duration_hours` (24)
- `election_vote_energy_cost` (120)
- `party_create_gold_cost` (75)
- `law_default_duration_hours` (24)
- `law_vote_energy_cost` (60)
