# fumo-captcha

A small "click all images of \<character\>" captcha built with PHP 8.1+.
No dependencies and no build step.

## Download

### Option 1: Git

```bash
git clone https://github.com/stoptryingtodoxme321/fumo-captcha.git
cd fumo-captcha
```

### Option 2: ZIP

1. Open the repository page on GitHub.
2. Click **Code** -> **Download ZIP**.
3. Extract the archive.
4. Open a terminal in the extracted folder.

## Run

Start PHP's built-in server with the included router:

```bash
php -S localhost:8000 router.php
```

Then open:

```text
http://localhost:8000
```

The router keeps `./images` private so every tile is served through `/img/...`
with the session check applied.

## Images and manifest

Tiles live in `./images` and are listed in `./images/manifest.json`.
If you add or remove art, rebuild the manifest with:

```powershell
powershell -ExecutionPolicy Bypass -File .\rebuild-manifest.ps1
```

A character needs at least `MIN_PER_CHARACTER` images to be used.

## Notes

- `views/home.php` and the PowerShell helper scripts are intentionally not
  included in this repository.
- Solving the captcha sets a session cookie and unlocks the home page.