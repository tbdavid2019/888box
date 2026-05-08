#!/bin/bash

# 888box 一鍵安裝腳本
# 適用於 Linux (x86_64 / arm64)

set -e

echo "🚀 歡迎使用 888box 一鍵安裝腳本"
echo "------------------------------------------------"

# 1. 檢查環境
if ! command -v docker &> /dev/null; then
    echo "❌ 錯誤: 未偵測到 Docker，請先安裝 Docker。"
    exit 1
fi

if ! docker compose version &> /dev/null && ! docker-compose version &> /dev/null; then
    echo "❌ 錯誤: 未偵測到 Docker Compose，請先安裝。"
    exit 1
fi

COMPOSE_CMD="docker compose"
if ! docker compose version &> /dev/null; then
    COMPOSE_CMD="docker-compose"
fi

# 2. 詢問儲存設定
echo "📦 儲存方式設定"
read -p "❓ 是否使用 S3 儲存 (例如 AWS, Cloudflare R2, MinIO)? [y/N]: " USE_S3
USE_S3=${USE_S3:-n}

if [[ "$USE_S3" =~ ^[Yy]$ ]]; then
    STORAGE_TYPE="s3"
    read -p "🔹 S3 Access Key ID: " S3_ACCESS_KEY_ID
    read -p "🔹 S3 Access Key Secret: " S3_ACCESS_KEY_SECRET
    read -p "🔹 S3 Region (例如 us-east-1): " S3_REGION
    read -p "🔹 S3 Bucket Name: " S3_BUCKET
    read -p "🔹 S3 Endpoint (若使用 R2/MinIO 請填寫, AWS 留空): " S3_ENDPOINT
    read -p "🔹 S3 CDN Domain (選填，例如 https://cdn.example.com): " S3_CDN_DOMAIN
    read -p "🔹 S3 ACL (R2 建議留空, AWS 建議 public-read): " S3_ACL
else
    STORAGE_TYPE="local"
    echo "✅ 將使用本地儲存 (storage/i/)"
fi

# 3. 初始化目錄與權限
echo "📂 正在初始化目錄結構..."
mkdir -p storage/i
# 嘗試設定權限 (UID 33 是 Docker 內 www-data 的預設值)
if [ "$EUID" -eq 0 ]; then
    chown -R 33:33 storage
else
    echo "⚠️ 警告: 非 root 執行，請確保 storage/ 目錄具備寫入權限 (建議 chown -R 33:33 storage)"
fi

# 3. 準備 .env
if [ ! -f .env ]; then
    echo "📝 正在產生預設 .env 配置..."
    cat > .env <<EOF
# 888box 配置
DEMO_MODE = false
ALLOW_PASSWORD_RESET = false

# S3 儲存設定 (選填)
# STORAGE = local
# S3_ACCESS_KEY_ID =
# S3_ACCESS_KEY_SECRET =
# S3_REGION = 
# S3_BUCKET = 
# S3_ENDPOINT = 
# S3_CDN_DOMAIN =
# S3_ACL =
EOF
    chmod 600 .env
fi

# 5. 啟動容器
echo "🐳 正在啟動 Docker 容器..."
$COMPOSE_CMD up -d --build

# 再次確保權限正確 (透過 Docker 執行，避免宿主機無權限問題)
echo "🔒 正在修復容器內目錄權限..."
docker exec 888box chown -R 33:33 /var/www/html/storage

echo "------------------------------------------------"
echo "✅ 容器已啟動！現在開始設定管理員帳號。"

# 6. 互動式帳號設定
read -p "👤 請輸入管理員帳號 (預設: admin): " ADMIN_USER
ADMIN_USER=${ADMIN_USER:-admin}

stty -echo
read -p "🔑 請輸入管理員密碼: " ADMIN_PASS
stty echo
echo ""

if [ -z "$ADMIN_PASS" ]; then
    echo "❌ 錯誤: 密碼不能為空！"
    exit 1
fi

echo "⚙️ 正在初始化資料庫與配置..."
docker exec 888box php -r "
    require '/var/www/html/config/schema.php';
    \$pdo = new PDO('sqlite:/var/www/html/storage/database.db');
    createCoreTables(\$pdo);
    
    // 初始化帳號
    \$hashed = password_hash('$ADMIN_PASS', PASSWORD_DEFAULT);
    \$token = bin2hex(random_bytes(16));
    \$stmt = \$pdo->prepare('INSERT OR REPLACE INTO users (username, password, token) VALUES (?, ?, ?)');
    \$stmt->execute(['$ADMIN_USER', \$hashed, \$token]);

    // 注入儲存設定
    \$configs = [
        'storage' => '$STORAGE_TYPE',
        'max_uploads_per_day' => '50',
        'max_file_size' => '104857600',
        'max_video_size' => '500',
        's3_access_key_id' => '$S3_ACCESS_KEY_ID',
        's3_access_key_secret' => '$S3_ACCESS_KEY_SECRET',
        's3_region' => '$S3_REGION',
        's3_bucket' => '$S3_BUCKET',
        's3_endpoint' => '$S3_ENDPOINT',
        's3_cdn_domain' => '$S3_CDN_DOMAIN',
        's3_acl' => '$S3_ACL'
    ];

    foreach (\$configs as \$k => \$v) {
        if (\$v !== '') {
            \$stmt = \$pdo->prepare('INSERT OR REPLACE INTO configs (\"key\", value) VALUES (?, ?)');
            \$stmt->execute([\$k, \$v]);
        }
    }
    
    // 建立 RSS 相關鎖定目錄
    if (!file_exists('/var/www/html/storage/locks')) {
        mkdir('/var/www/html/storage/locks', 0777, true);
    }

    echo \"✅ 管理員 \$ADMIN_USER 已成功初始化。\\n\";
    echo \"✅ 儲存配置 (\$STORAGE_TYPE) 已寫入資料庫。\\n\";
"


echo "------------------------------------------------"
echo "🎉 安裝完成！"
echo "🌐 存取位址: http://your-ip:6767/admin"
echo "👉 建議立即進入後台進行更詳細的設定。"
