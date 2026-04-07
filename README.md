# Geopolitical MMO Module 1

Production-ready PHP 8.3 + MariaDB backend/frontend for a geopolitical MMO with:

- Real countries + regions stored in DB
- Dynamic country metadata (name/flag/color)
- Auth, player profile, travel, and history APIs
- Interactive Leaflet map driven by live API data
- Player positioning and travel workflow
- Geo-IP based auto-assignment at registration (IP API -> MaxMind -> fallback)

See `docs/DEPLOYMENT.md` for AlmaLinux setup and `database/seed_world.php` for map import.
