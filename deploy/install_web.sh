#!/usr/bin/env bash
# Ragna Next - pasang website statis di VPS (nginx yang sama dengan FluxCP).
# Usage (root): bash install_web.sh [domain]     default domain: ragna-next.cc
# Aman dijalankan ulang. Update otomatis: cron fetch + reset ke origin/main tiap menit.
set -euo pipefail

DOMAIN=${1:-ragna-next.cc}
REPO=https://github.com/tatanugraha/ragna-next.git
WEB_DIR=/var/www/ragna-next
SITE=/etc/nginx/sites-available/ragna-next

# --- source ---
if [[ ! -d $WEB_DIR/.git ]]; then
  rm -rf "$WEB_DIR"
  git clone --depth 1 "$REPO" "$WEB_DIR"
else
  git -C "$WEB_DIR" fetch -q --depth 1 origin main
  git -C "$WEB_DIR" reset -q --hard origin/main
fi

# --- nginx (dibuat sekali; certbot menambah blok SSL ke file ini) ---
if [[ ! -f $SITE ]] || ! grep -q 'deploy|README' "$SITE"; then
  cat > "$SITE" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN www.$DOMAIN;
    root $WEB_DIR;
    index index.html;

    # repo & script deploy tidak boleh diakses publik
    location ~ /\.(git|ht) { deny all; }
    location ~ ^/(deploy|README\.md) { deny all; }

    location ~* \.(css|js|png|jpg|jpeg|webp|svg|ico|woff2?)\$ {
        expires 7d;
        add_header Cache-Control "public";
    }

    location / {
        try_files \$uri \$uri/ =404;
    }
}
NGINX
  ln -sf "$SITE" /etc/nginx/sites-enabled/ragna-next
  nginx -t
  systemctl reload nginx
fi

# --- auto-deploy: tarik update dari GitHub tiap menit ---
cat > /etc/cron.d/ragna-next <<CRON
* * * * * root git -C $WEB_DIR fetch -q --depth 1 origin main && git -C $WEB_DIR reset -q --hard origin/main >/dev/null 2>&1
CRON

# --- SSL (hanya kalau DNS sudah mengarah ke VPS ini dan belum ada sertifikat) ---
if [[ ! -d /etc/letsencrypt/live/$DOMAIN ]]; then
  MY_IP=$(curl -s4 https://api.ipify.org || true)
  DNS_IP=$(getent ahostsv4 "$DOMAIN" | awk 'NR==1{print $1}')
  if [[ -n $MY_IP && $MY_IP == "$DNS_IP" ]]; then
    certbot --nginx --non-interactive --agree-tos --register-unsafely-without-email --redirect \
      -d "$DOMAIN" -d "www.$DOMAIN"
  else
    echo "DNS $DOMAIN -> ${DNS_IP:-?}, VPS -> ${MY_IP:-?}. SSL dilewati."
    echo "Setelah A record @ diarahkan ke VPS, jalankan ulang: bash $WEB_DIR/deploy/install_web.sh"
  fi
fi

echo "Website terpasang di $WEB_DIR untuk $DOMAIN"
