# Ragna Next

Website resmi Ragna Next — https://ragna-next.cc

Situs statis (HTML + CSS), tanpa build step. File di root repo langsung di-deploy ke `public_html` hosting.

## Struktur

```
index.html        halaman utama
assets/style.css  styling
assets/main.js    interaksi halaman dan render changelog
assets/changelog.json  seed/fallback changelog; FluxCP CMS menjadi sumber live
```

## Deploy

Website di-host di VPS game (`38.134.189.115`), nginx yang sama dengan FluxCP (`cp.ragna-next.cc`).

- Update: push ke branch `main` → cron di VPS menyamakan folder dengan `origin/main` tiap menit ke `/var/www/ragna-next`.
- Pasang / pasang ulang (root di VPS): `bash /var/www/ragna-next/deploy/install_web.sh`
- DNS: A record `@` → `38.134.189.115`, `www` CNAME → `ragna-next.cc`.
- SSL: script otomatis menjalankan certbot kalau DNS sudah mengarah ke VPS (jalankan ulang setelah DNS pindah).

## Changelog

GM dan admin mengedit news/changelog langsung dari homepage melalui tombol **Edit News** setelah login di FluxCP. Homepage membaca sesi FluxCP dan menampilkan status akun agar pemain tidak mengira sesi hilang saat kembali dari panel akun. File sesi FluxCP tersimpan di `/var/lib/php/sessions`; cron website hanya mereset source website yang dilacak Git. Masa sesi login diatur 14 hari.

Homepage and patcher read the same public feed from `/akun/?module=changelog&action=feed`; `/akun/?module=changelog&action=portal` reports the current login state and serves editor data only to authorized staff. `assets/changelog.json` is used as seed/fallback before the feed is installed. The server checks GM access (`AccountLevel::LOWGM`) on both editor and save actions.

Setiap item memakai `title`, `date` (`YYYY-MM-DD`), `category` (`event`, `info`, atau `update`), dan `summary`. Klik item di patcher membuka `https://ragna-next.cc/#changelog`. Untuk memasang modul CMS ke FluxCP, jalankan ulang `bash /var/www/ragna-next/deploy/install_web.sh`; data CMS disimpan di `/var/lib/ragna-next/changelog.json`, di luar folder website yang di-reset oleh cron.
