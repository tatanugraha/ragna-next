# Ragna Next

Website resmi Ragna Next — https://ragna-next.cc

Situs statis (HTML + CSS), tanpa build step. File di root repo langsung di-deploy ke `public_html` hosting.

## Struktur

```
index.html        halaman utama
assets/style.css  styling
```

## Deploy

Website di-host di VPS game (`38.134.189.115`), nginx yang sama dengan FluxCP (`cp.ragna-next.cc`).

- Update: push ke branch `main` → cron di VPS menyamakan folder dengan `origin/main` tiap menit ke `/var/www/ragna-next`.
- Pasang / pasang ulang (root di VPS): `bash /var/www/ragna-next/deploy/install_web.sh`
- DNS: A record `@` → `38.134.189.115`, `www` CNAME → `ragna-next.cc`.
- SSL: script otomatis menjalankan certbot kalau DNS sudah mengarah ke VPS (jalankan ulang setelah DNS pindah).
