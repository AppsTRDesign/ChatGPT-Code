# Grand Strategy Political Simulation — Phase 1 (Architecture)

## 0) Overall Architecture Summary

This project is designed as a **modular monorepo** with strict separation between simulation domain services, API transport, and user interface.

- **Frontend (React + Tailwind + Zustand + React Query + Framer Motion + Leaflet)**
  - UI composition, map rendering, player interactions, real-time subscriptions.
- **Backend (Node.js 16.20.2 + Express + Socket.io + MariaDB)**
  - Authoritative simulation logic, state transitions, policy checks, elections, travel, war, economy.
- **Shared layer**
  - Cross-layer type contracts, constants, and DTO schemas.
- **Language layer (`lang/`)**
  - Canonical translation files (EN/TR first), synchronized to DB-backed translation management.

Architecture style:
- **Module-oriented backend** (vertical slices by domain).
- **Feature-oriented frontend** (map/politics/travel/war page domains).
- **Event-assisted simulation** (jobs + sockets + snapshot tables).
- **Production-first deployment** for Plesk/Apache/Node 16/MariaDB.

---

## 1) Full Folder Structure

```text
.
├── backend/
│   ├── config/
│   ├── core/
│   ├── database/
│   ├── jobs/
│   ├── lang/
│   │   ├── en.json
│   │   └── tr.json
│   ├── modules/
│   │   ├── admin/
│   │   ├── auth/
│   │   ├── chat/
│   │   ├── cities/
│   │   ├── countries/
│   │   ├── economy/
│   │   ├── i18n/
│   │   ├── inventory/
│   │   ├── notifications/
│   │   ├── politics/
│   │   ├── regions/
│   │   ├── stats/
│   │   ├── travel/
│   │   ├── users/
│   │   └── war/
│   ├── sockets/
│   └── utils/
├── docs/
│   └── phase-01-architecture.md
├── frontend/
│   ├── app/
│   │   └── routes/
│   ├── components/
│   │   ├── cards/
│   │   ├── economy/
│   │   ├── map/
│   │   ├── politics/
│   │   ├── travel/
│   │   ├── ui/
│   │   └── war/
│   ├── hooks/
│   ├── i18n/
│   ├── lang/
│   │   ├── en.json
│   │   └── tr.json
│   ├── lib/
│   ├── pages/
│   │   ├── admin/
│   │   ├── city/
│   │   ├── country/
│   │   ├── dashboard/
│   │   ├── elections/
│   │   ├── inventory/
│   │   ├── map/
│   │   ├── profile/
│   │   ├── travel/
│   │   └── war/
│   ├── services/
│   └── store/
├── lang/
│   ├── en.json
│   └── tr.json
├── scripts/
└── shared/
```

---

## 2) Gameplay System Dependency Map

```text
[Auth + User Profile]
   └──> [GeoIP Assignment Engine]
          └──> [Country/Region/City Residence]
                 ├──> [Travel Engine + Visa/Permit]
                 │      └──> [Map Movement + Flight Timers]
                 ├──> [Economy Engine]
                 │      ├──> [Work + Salary + Tax]
                 │      └──> [City/Country Treasury]
                 ├──> [Politics Engine]
                 │      ├──> [Elections + Voting]
                 │      └──> [Policies: tax, visa, military]
                 ├──> [Governor + City Projects]
                 │      └──> [City Stat Growth + Migration Attractiveness]
                 ├──> [War Engine]
                 │      ├──> [Region Battles]
                 │      └──> [Control Transfer + Economy Impact]
                 └──> [Stats + Snapshots + Leaderboards]

Cross-cutting: [Inventory], [Notifications], [Chat], [Realtime Sockets], [i18n]
```

Design principle: simulation decisions always run server-side first; frontend only reflects authoritative state.

---

## 3) i18n System Design (EN/TR first)

### 3.1 File model
- Canonical JSON translation files in:
  - `lang/en.json`, `lang/tr.json`
- Runtime copies for frontend and backend bootstrapping:
  - `frontend/lang/*.json`
  - `backend/lang/*.json`

### 3.2 Key format
- Dot-notation key-based translations:
  - `common.save`
  - `map.fly_here`
  - `city.population`

### 3.3 Resolution priority
1. User-selected language (profile if logged in, localStorage if guest)
2. Browser locale (`Accept-Language` / `navigator.language`)
3. Fallback to `en`

### 3.4 Admin-manageable architecture
- DB tables (Phase 2): `languages`, `translation_keys`, `translation_values`.
- Admin functions:
  - Add/edit/disable language.
  - Add/edit translation keys and values.
  - Export/import JSON files.
- Safety:
  - Missing keys fallback to English.
  - If English key missing, key string itself shown for debugging.

---

## 4) Deployment Compatibility Summary (Target Environment)

Target: **AlmaLinux + Plesk + Apache + Node.js 16.20.2 + MariaDB + PHP 8.3**

Compatibility choices:
- Avoid Node 18+ runtime APIs.
- Use CommonJS or Node 16-compatible ESM configuration.
- Avoid native add-ons unless necessary.
- Keep background tasks as Node cron/jobs + DB state machine.
- Use Apache/Plesk Node.js integration for backend process.
- Serve React build as static assets, proxy API and sockets to Node app.
- Optional PHP 8.3 helper endpoints allowed for panel-side maintenance scripts, not core simulation logic.

---

## 5) Phase 1 Scope (Completed)

Phase 1 defines:
1. Product architecture boundaries.
2. Module map and folder contract.
3. Simulation dependency graph.
4. i18n architecture and fallback rules.
5. Deployment constraints for target production stack.

### Backend responsibilities (Phase 1 definition)
- Identity/session lifecycle.
- Simulation authority and anti-cheat enforcement.
- Policy/state validation for travel, visas, war, votes, economy.
- Snapshot generation and event publishing.

### Frontend responsibilities (Phase 1 definition)
- Strategy-oriented UX and map interaction.
- Animation and view state.
- API orchestration + optimistic UI where safe.
- Language switching and locale-aware presentation.

### Data contract responsibilities (Phase 1 definition)
- Shared DTOs for map entities, cards, travel responses, and election summaries.
- Versioned API namespaces (`/api/v1/...`) for long-term compatibility.

---

## 6) Next Phase Entry Criteria (Phase 2: Database)

Phase 2 starts when:
- Architecture contract is accepted.
- Table naming conventions are approved.
- Index priorities and cardinality assumptions are confirmed.

Deliverables next phase:
- Full MariaDB schema SQL.
- Foreign keys, indexes, status enums.
- Seeds for base languages and structural bootstrap records.
