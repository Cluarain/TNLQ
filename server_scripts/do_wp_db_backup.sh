#!/bin/bash

# --- Настройки ---
# Путь к папке с бэкапами (создайте её заранее)
BACKUP_DIR="/opt/server_scripts/wp_db_backups"
# Количество дней хранения бэкапов (старые будут удаляться)
RETENTION_DAYS=7
# Путь к корню WordPress (нужен для wp-cli, если будете использовать его)
WP_ROOT="/var/www/html"

# --- Если у вас установлен wp-cli (рекомендуется) ---
# Используем wp-cli для получения параметров БД и создания дампа
if command -v wp &> /dev/null; then
    cd "$WP_ROOT" || exit 1
    # Создаём дамп через wp-cli (уже сжатый gzip)
    wp db export - --allow-root | gzip > "$BACKUP_DIR/db_$(date +%Y%m%d_%H%M%S).sql.gz"
    echo "Backup via wp-cli created"
else
    # --- Альтернативный вариант: читаем параметры из wp-config.php ---
    # Извлекаем данные для подключения к БД
    DB_NAME=$(grep "DB_NAME" "$WP_ROOT/wp-config.php" | awk -F"'" '{print $4}')
    DB_USER=$(grep "DB_USER" "$WP_ROOT/wp-config.php" | awk -F"'" '{print $4}')
    DB_PASS=$(grep "DB_PASSWORD" "$WP_ROOT/wp-config.php" | awk -F"'" '{print $4}')
    DB_HOST=$(grep "DB_HOST" "$WP_ROOT/wp-config.php" | awk -F"'" '{print $4}')

    # Проверяем, что все переменные получены
    if [[ -z "$DB_NAME" || -z "$DB_USER" || -z "$DB_PASS" ]]; then
        echo "Error: no credentials for db connection"
        exit 1
    fi

    # Создаём дамп с помощью mysqldump и сжимаем
    mysqldump --host="$DB_HOST" --user="$DB_USER" --password="$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/db_$(date +%Y%m%d_%H%M%S).sql.gz"
    echo "Backup via mysqldump created"
fi

# --- Удаление старых бэкапов ---
find "$BACKUP_DIR" -name "db_*.sql.gz" -type f -mtime +$RETENTION_DAYS -delete
echo "Old Backups (older $RETENTION_DAYS days) deleted"
