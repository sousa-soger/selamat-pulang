<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>


## Required project design system

All UI work MUST follow DESIGN.md (Auralis), the permanent visual source of truth selected by the user. Apply this to every route, authentication, accounts, organization, dashboards, modules, dialogs, and future features. Never reintroduce the previous green/sage theme.

Use white #FFFFFF canvases, charcoal #1C1C1E emphasis surfaces, indigo #4F46E5 primary actions, cyan #06B6D4 accents, ink #111827, secondary text #4B5563, and borders #E5E7EB. Use Geist for headings/body and JetBrains Mono for metadata. Use an 8px spacing base and 8px card/control radii. Preserve responsive layouts, restrained motion, and clear hierarchy. Green is reserved for meaningful success states, never the base theme.

resources/css/auralis.css owns shared visual tokens and components; resources/css/app.css and adapt360.css provide structure. Reuse these tokens instead of adding conflicting page palettes. public/css/accounts.css must follow the same system. Read DESIGN.md before visual changes. The referenced source HTML is not supplied; do not invent claims of matching its exact composition.

## Video workspace

For all future video-related work, use C:\! !\adapt360-video. Do not recreate adapt360-video inside this Laravel repository. The video project was moved to that external directory at the user's request.
