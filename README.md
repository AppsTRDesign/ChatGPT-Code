# NoaSoft Converter

NoaSoft Converter is a responsive PHP 8.2 web application for converting audio, video, and image files directly in the browser. It is tuned for deployment on a Plesk-managed AlmaLinux 8 host with FFmpeg 4.2+ and implements a fully AJAX-driven workflow with SweetAlert2 notifications, Dropzone-powered uploads, and configurable presets for the most common social media formats.

## Key Features

- **Unified landing page** describing the service, how-to steps, and quick links to each conversion tool.
- **Dedicated converters** for audio, video, and image processing with per-category validation so only compatible formats can be uploaded.
- **Drag & drop or click-to-select uploads** powered by Dropzone, with custom styling, file count badges, size checks, and live file metadata.
- **Sequential job pipeline** that tracks each file through upload, conversion, and download stages while rendering distinct progress bars and percentages.
- **SweetAlert2 messaging** for every validation, warning, or success state (no native browser alerts are used).
- **Cancelable jobs** that stop uploads, conversion, and downloads immediately, cleaning up any temporary or output artifacts on the server.
- **Preset management** for audio, video, and image outputs. Selecting a preset auto-fills and locks FFmpeg/GD options, while "custom" re-enables manual control.
- **SEO-friendly routing** via `.htaccess` rewrites so public URLs map to `/audio-convert`, `/video-convert`, `/image-convert`, `/faq`, `/contact`, and `/copyright`.
- **Config-driven limits** that centralize upload caps, mail settings, base URLs, and navigation links in `site/includes/config.php`.
- **Contact form with PHP mail** support. SMTP can be toggled from the configuration file if external delivery is required.

## Technology Stack

| Layer | Technology |
| --- | --- |
| Language | PHP 8.2 |
| Media Processing | FFmpeg 4.2+ for audio/video, GD/ImageMagick (via PHP) for image tweaks |
| Front-end Libraries | Dropzone 5.9.3, SweetAlert2, Alpine.js 3.13.7 |
| Styling | Custom responsive CSS (`site/assets/css/style.css`) built on the provided blue/black theme |
| JavaScript | Modular helper (`site/assets/js/converter.js`) orchestrating uploads, polling, and preset logic |
| Routing | Apache `.htaccess` rewrites |

## Project Structure

```
site/
├── assets/
│   ├── css/style.css          # Global theme with mobile-first form and Dropzone styles
│   └── js/
│       ├── converter.js       # Core converter logic (Dropzone setup, progress, presets)
│       └── main.js            # Navigation toggles and shared UI helpers
├── ajax/
│   ├── process.php            # Handles uploads, invokes FFmpeg/GD, and streams progress updates
│   ├── status.php             # Reports job status, percentages, and next actions
│   ├── cancel.php             # Cancels active jobs and purges temporary files
│   └── contact.php            # Processes contact form submissions using config-driven mail settings
├── includes/
│   ├── config.php             # Central configuration for site metadata, limits, and SMTP
│   ├── functions.php          # Helper utilities, job lifecycle management, routing helpers
│   ├── header.php / footer.php# Shared layout scaffolding and asset loading
├── storage/                   # Uploads, outputs, and job metadata (auto-created)
├── index.php                  # Landing page with feature overview and CTA links
├── audio.php                  # Audio converter view
├── video.php                  # Video converter view with presets
├── image.php                  # Image converter view with presets
├── faq.php                    # Frequently asked questions content
├── contact.php                # Contact form page using SweetAlert feedback
├── copyright.php              # Legal / copyright notice
├── download.php               # Secure download endpoint with progress feedback
└── .htaccess                  # SEO rewrites and caching headers
```

## Configuration

1. Copy `site/includes/config.php` and adjust values as needed:
   - `site.base_url` – set if the application lives in a subdirectory.
   - `upload.max_files` and `upload.max_size_mb` – enforce the maximum files per batch and per-file size.
   - `mail` block – configure the destination address and optional SMTP credentials. When `smtp.enabled` is `true`, the script uses PHP's `mail()` fallback unless SMTP parameters are supplied.
2. Ensure the `site/storage/` directory (and its subfolders) are writable by the web server. The application auto-creates `uploads`, `output`, and `jobs` folders on demand.
3. Update `site/includes/config.php` any time branding, navigation, or limits need to change—no code edits elsewhere are required.

## Installation & Deployment

1. **Server requirements:** PHP 8.2 with `proc_open`, `exec`, and `shell_exec` enabled; FFmpeg 4.2+ installed and available in `$PATH`; GD or Imagick for image adjustments.
2. **Clone or upload** the repository to the `game.noasoft.org` root (or desired virtual host).
3. **Set permissions** so that `site/storage/` is writable (e.g., `chmod -R 775 site/storage`).
4. **Configure Apache/Nginx:** For Apache, the provided `.htaccess` handles rewrites automatically. For Nginx or Plesk, translate the rewrite rules to the respective configuration.
5. **Verify PHP mail:** If SMTP is required, fill in the credentials in `config.php`; otherwise, ensure the server's sendmail transport is active.
6. **Visit the site** and test each converter (audio/video/image) to confirm FFmpeg binaries are detected and progress bars advance as expected.

## Conversion Workflow

1. Users drag or click to add up to five files. Dropzone filters the allowed extensions per converter (audio/video/image) and shows live file chips with size data.
2. Pressing the convert button starts a staged progress UI:
   - **Upload stage:** Each file uploads sequentially with a dedicated progress bar and status label.
   - **Conversion stage:** Once uploaded, FFmpeg or GD processes the file. Output percentages are streamed back through AJAX polling.
   - **Download stage:** When conversion completes, the download button activates. Clicking it shows a download progress bar before streaming the file.
3. Cancelling at any stage stops the active request, marks the job as cancelled, purges source/output artifacts, and resets the interface.
4. Successful downloads trigger automatic cleanup of temporary files and restore the form to its initial state.

## SEO-Friendly Routes

The `.htaccess` file maps human-readable slugs to their PHP counterparts:

- `/audio-convert` → `audio.php`
- `/video-convert` → `video.php`
- `/image-convert` → `image.php`
- `/faq` → `faq.php`
- `/contact` → `contact.php`
- `/copyright` → `copyright.php`

Legacy Turkish filenames were removed, so only these canonical URLs remain.

## JavaScript & Styling Notes

- `converter.js` exposes an `nsInitializeConverter` helper that accepts a configuration object from each converter page. It wires Dropzone, SweetAlert2, the multi-stage progress bars, cancellation logic, and preset synchronization.
- The application loads Alpine.js (deferred) for lightweight interactivity and Dropzone 5.9.3 for drag-and-drop uploads via CDN, satisfying the user's asset requirements.
- `site/assets/css/style.css` implements the supplied design tokens (blue/black palette) with responsive forms, stacked grid layouts under 900px, and compact Dropzone previews that gracefully truncate long filenames.

## Contact & Notifications

- The contact form posts to `ajax/contact.php`, which uses the config-driven `mail` settings.
- All user-facing feedback—errors, warnings, and confirmations—are surfaced through SweetAlert2 modals, ensuring consistent styling across devices.

## Maintenance Tips

- Periodically clear the `site/storage/` directory if large files accumulate, though the application cleans up after each job.
- To introduce new presets, edit the preset arrays in `audio.php`, `video.php`, or `image.php` and mirror any server-side expectations in `ajax/process.php`.
- Before deploying updates, run `php -l` or your preferred static analysis on the PHP files to catch syntax issues.

## Testing

A quick syntax check across all PHP scripts:

```bash
find site -name "*.php" -print0 | xargs -0 -n1 php -l
```

For end-to-end verification, upload sample media files (within the configured size limit) to each converter and confirm that uploads, conversions, downloads, and cancellations behave as expected.

