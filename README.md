# Telegram Automation Suite

This project provides a multilingual (TR/EN) desktop application built with PySide6 and Telethon. It targets Python **3.13** on Windows 11 and offers the following automation tools:

* Multi-account OTP login with session storage.
* Ban checking to automatically prune unusable sessions.
* Activity-based member scanning from target groups/channels with per-session progress indicators.
* Bulk member invitations that respect Telegram flood limits and keep the saved user list in sync.
* Adjustable rate-limit settings to help avoid bans.

The GUI dynamically switches between Turkish and English without restarting the application.

## Project Structure

```
app/
  gui.py              # Qt user interface widgets
  telethon_manager.py # Async helpers wrapping Telethon
  translations.py     # Translation dictionaries and helper
main.py               # Application entry-point
requirements.txt      # Python dependencies
session/              # Generated Telethon session files (OTP logins)
users/                # Saved user JSON files from scans
config.json           # Generated on first run (API credentials + rate limits)
```

## Prerequisites

1. Install **Python 3.13** for Windows from [python.org](https://www.python.org/downloads/windows/). During installation, enable the option to “Add Python to PATH”.
2. Install the Microsoft Visual C++ Redistributable (required by PySide6) if it is not already present. You can download it from [aka.ms/vs/17/release/vc_redist.x64.exe](https://aka.ms/vs/17/release/vc_redist.x64.exe).
3. Install Git (optional but recommended) from [git-scm.com](https://git-scm.com/downloads).
4. Create a Telegram application at <https://my.telegram.org>. Note your **API ID** and **API Hash**.

## Installation

Open **PowerShell** and run the following commands:

```powershell
git clone https://github.com/your-user/telegram-automation-suite.git
cd telegram-automation-suite
py -3.13 -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install --upgrade pip
pip install -r requirements.txt
```

> Replace the GitHub URL with your fork or local path if needed.

## Running the Application

```powershell
py -3.13 main.py
```

On the first launch the app will prompt for your Telegram API credentials. They are stored in `config.json` alongside the rate-limit preferences.

### OTP Login & Session Management

* Open the **Sessions** tab.
* Enter a phone number and click **Send Code**. Provide the OTP (and 2FA password if required).
* Successful logins create `.session` files under the `session/` folder and the list updates instantly.

### Ban Checking

* Navigate to **Ban Check** and click the button to test all stored sessions.
* Invalid or banned sessions are removed automatically and reported in the log.

### Scanning Members

1. Choose one or more sessions, enter the target group/channel username or invite link, and set activity filters (minutes/hours/days).
2. Optionally set a member limit.
3. Click **Start** to begin. Progress bars appear per session. Flood-wait timers display below the affected session.
4. The resulting members are saved as JSON in the `users/` directory. The log shows the save location.

### Adding Members to a Group

1. Choose target group/channel and select a saved users JSON file.
2. Pick the sessions you want to use and click **Start**.
3. Progress bars track each session. Successfully added users (and already-in-group users) are removed from the JSON file to avoid reprocessing.

### Rate Limits & Language

* The **Settings** tab lets you adjust the delays between actions/sessions and swap the interface language between Turkish and English. Changes apply instantly.

## Directories Created at Runtime

* `session/` – contains `.session` files generated via OTP login. Delete entries here to remove accounts.
* `users/` – receives JSON exports from the scanner. The adder consumes and updates these files.
* `config.json` – stores API credentials and rate limit preferences.

## Flood-Wait Handling

When Telegram enforces a flood-wait, the GUI displays a countdown under the affected session. Operations automatically resume after the timer expires.

## Troubleshooting

* Ensure that the API credentials are correct; wrong values prevent the app from starting.
* If Windows Defender SmartScreen blocks the app, click “More info” and “Run anyway”.
* When Telethon raises `FloodWaitError`, increase the delay values under **Settings**.

## License

This project is provided as-is for educational purposes.
