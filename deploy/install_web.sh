#!/usr/bin/env bash
# Ragna Next - pasang website + panel akun (FluxCP di /akun) di VPS.
# Usage (root): bash install_web.sh [domain]     default domain: ragna-next.cc
# Aman dijalankan ulang. Update otomatis: cron fetch + reset ke origin/main tiap menit.
# FluxCP harus sudah terpasang di $FLUX_DIR (lihat deploy/install_fluxcp.sh di repo server).
set -euo pipefail

DOMAIN=${1:-ragna-next.cc}
CP_DOMAIN=cp.$DOMAIN
REPO=https://github.com/tatanugraha/ragna-next.git
WEB_DIR=/var/www/ragna-next
FLUX_DIR=/var/www/fluxcp
CP_ROOT=/var/www/cp            # berisi symlink akun -> $FLUX_DIR, supaya FluxCP tampil di /akun
SITE=/etc/nginx/sites-available/ragna-next
CP_SITE=/etc/nginx/sites-available/fluxcp
PHP_SOCK=$(ls /run/php/php[0-9]*-fpm.sock | head -n1)

# --- source ---
if [[ ! -d $WEB_DIR/.git ]]; then
  rm -rf "$WEB_DIR"
  git clone --depth 1 "$REPO" "$WEB_DIR"
else
  git -C "$WEB_DIR" fetch -q --depth 1 origin main
  git -C "$WEB_DIR" reset -q --hard origin/main
fi

# --- FluxCP: tema ragnanext, alamat /akun di domain utama ---
ln -sfn "$WEB_DIR/fluxcp-theme/ragnanext" "$FLUX_DIR/themes/ragnanext"
mkdir -p "$CP_ROOT"
ln -sfn "$FLUX_DIR" "$CP_ROOT/akun"
sed -i \
  -e "s|'ServerAddress'\(\s*\)=> '[^']*'|'ServerAddress'\1=> '$DOMAIN'|" \
  -e "s|'BaseURI'\(\s*\)=> '[^']*'|'BaseURI'\1=> '/akun'|" \
  -e "s|'ThemeName'\(\s*\)=> array([^)]*)|'ThemeName'\1=> array('ragnanext')|" \
  "$FLUX_DIR/config/application.php"

# --- nginx ---
site_body() {
  cat <<NGINX
    root $WEB_DIR;
    index index.html;

    # repo, script deploy & source tema tidak boleh diakses publik
    location ~ /\.(git|ht) { deny all; }
    location ~ ^/(deploy|fluxcp-theme|README\.md) { deny all; }

    location ~* \.(css|js|png|jpg|jpeg|webp|svg|ico|woff2?)\$ {
        expires 7d;
        add_header Cache-Control "public";
    }

    location / {
        try_files \$uri \$uri/ =404;
    }

    # Panel akun (FluxCP)
    location = /akun { return 301 /akun/; }
    location ^~ /akun/ {
        root $CP_ROOT;
        index index.php;
        client_max_body_size 8m;

        # file konfigurasi, source, dan data internal FluxCP tidak boleh diakses publik
        location ~ ^/akun/(config|lib|modules|themes/.*\.php|data/(logs|schemas|tmp|templates|paypal)|addons/[^/]+/(config|lib|modules)) { deny all; }
        location ~ /\.(git|ht) { deny all; }

        location ~ \.php\$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:$PHP_SOCK;
        }

        try_files \$uri \$uri/ /akun/index.php?\$args;
    }
NGINX
}

# cp.<domain> dan akses lewat IP -> panel akun di domain utama.
# Pakai location (bukan return di level server) supaya challenge certbot tetap bisa lewat.
cp_redirect() {
  echo "    location / { return 301 https://$DOMAIN/akun\$request_uri; }"
}

ssl_lines() {
  cat <<NGINX
    ssl_certificate /etc/letsencrypt/live/$1/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$1/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
NGINX
}

write_sites() {
  if [[ -d /etc/letsencrypt/live/$DOMAIN ]]; then
    {
      echo "server {"
      echo "    listen 443 ssl;"
      echo "    listen [::]:443 ssl;"
      echo "    server_name $DOMAIN www.$DOMAIN;"
      ssl_lines "$DOMAIN"
      site_body
      echo "}"
      echo "server {"
      echo "    listen 80;"
      echo "    listen [::]:80;"
      echo "    server_name $DOMAIN www.$DOMAIN;"
      echo "    location / { return 301 https://$DOMAIN\$request_uri; }"
      echo "}"
    } > "$SITE"
  else
    { echo "server {"; echo "    listen 80;"; echo "    listen [::]:80;"; echo "    server_name $DOMAIN www.$DOMAIN;"; site_body; echo "}"; } > "$SITE"
  fi

  {
    echo "server {"
    echo "    listen 80 default_server;"
    echo "    listen [::]:80 default_server;"
    echo "    server_name $CP_DOMAIN _;"
    cp_redirect
    echo "}"
    if [[ -d /etc/letsencrypt/live/$CP_DOMAIN ]]; then
      echo "server {"
      echo "    listen 443 ssl default_server;"
      echo "    listen [::]:443 ssl default_server;"
      echo "    server_name $CP_DOMAIN;"
      ssl_lines "$CP_DOMAIN"
      cp_redirect
      echo "}"
    fi
  } > "$CP_SITE"

  ln -sf "$SITE" /etc/nginx/sites-enabled/ragna-next
  ln -sf "$CP_SITE" /etc/nginx/sites-enabled/fluxcp
  nginx -t
  systemctl reload nginx
}
write_sites

# --- auto-deploy: tarik update dari GitHub tiap menit ---
cat > /etc/cron.d/ragna-next <<CRON
* * * * * root git -C $WEB_DIR fetch -q --depth 1 origin main && git -C $WEB_DIR reset -q --hard origin/main >/dev/null 2>&1
CRON

# --- SSL (hanya kalau DNS sudah mengarah ke VPS ini dan belum ada sertifikat) ---
if [[ ! -d /etc/letsencrypt/live/$DOMAIN ]]; then
  MY_IP=$(curl -s4 https://api.ipify.org || true)
  DNS_IP=$(getent ahostsv4 "$DOMAIN" | awk 'NR==1{print $1}')
  if [[ -n $MY_IP && $MY_IP == "$DNS_IP" ]]; then
    certbot certonly --nginx --non-interactive --agree-tos --register-unsafely-without-email \
      -d "$DOMAIN" -d "www.$DOMAIN"
    write_sites
  else
    echo "DNS $DOMAIN -> ${DNS_IP:-?}, VPS -> ${MY_IP:-?}. SSL dilewati."
    echo "Setelah A record @ diarahkan ke VPS, jalankan ulang: bash $WEB_DIR/deploy/install_web.sh"
  fi
fi

echo "Website terpasang di $WEB_DIR untuk $DOMAIN, panel akun di https://$DOMAIN/akun/"
