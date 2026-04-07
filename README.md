# Geopolitical MMO Module 1

Production-ready PHP 8.3 + MariaDB backend/frontend for a geopolitical MMO with:

- Region-centric geopolitical model (countries/regions/independents in regions table)
- Country metadata lives in regions (country_name, government_type, color, capital_region_id)
- Auth, player profile, travel, and history APIs
- Interactive Leaflet map driven by live API data
- Player positioning and travel workflow
- Geo-IP based auto-assignment at registration (IP API -> MaxMind -> fallback)

See `docs/DEPLOYMENT.md` for AlmaLinux setup and `database/seed_world.php` for map import.
