#!/bin/bash

# --- Настройки ---
BACKUP_DIR="/opt/server_scripts/wp_db_backups"
RETENTION_DAYS=7
WP_ROOT="/var/www/html"

# Функция для логирования с датой
log_msg() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1"
}

# Создаем директорию если нет
mkdir -p "$BACKUP_DIR"

# --- Если у вас установлен wp-cli (рекомендуется) ---
if command -v wp &> /dev/null; then
    cd "$WP_ROOT" || exit 1
    # WP CLI обычно сам обрабатывает авторизацию из wp-config.php
    if wp db export - --allow-root 2>/dev/null | gzip > "$BACKUP_DIR/db_$(date +%Y%m%d_%H%M%S).sql.gz"; then
        log_msg "Backup via wp-cli created successfully"
    else
        log_msg "ERROR: Backup via wp-cli failed"
        exit 1
    fi
else
    # --- Альтернативный вариант: mysqldump ---
    
    # Извлекаем данные для подключения к БД
    # Используем более надежный парсинг, чтобы избежать проблем с комментариями или лишними пробелами
    DB_NAME=$(grep "^define.*DB_NAME" "$WP_ROOT/wp-config.php" | head -1 | awk -F"'" '{print $4}')
    DB_USER=$(grep "^define.*DB_USER" "$WP_ROOT/wp-config.php" | head -1 | awk -F"'" '{print $4}')
    DB_PASS=$(grep "^define.*DB_PASSWORD" "$WP_ROOT/wp-config.php" | head -1 | awk -F"'" '{print $4}')
    DB_HOST=$(grep "^define.*DB_HOST" "$WP_ROOT/wp-config.php" | head -1 | awk -F"'" '{print $4}')

    # Проверка на пустые значения
    if [[ -z "$DB_NAME" || -z "$DB_USER" || -z "$DB_PASS" ]]; then
        log_msg "ERROR: Could not retrieve DB credentials from wp-config.php"
        exit 1
    fi

    # Формируем имя файла
    BACKUP_FILE="$BACKUP_DIR/db_$(date +%Y%m%d_%H%M%S).sql.gz"

    # Выполняем mysqldump
    # --no-tablespaces: решает ошибку "Access denied ... PROCESS privilege"
    # --single-transaction: важно для InnoDB (WordPress использует его), обеспечивает консистентность без блокировки таблиц
    # 2>&1: перенаправляем stderr в stdout, чтобы увидеть ошибки в логе, если они критические
    
    mysqldump \
        --host="$DB_HOST" \
        --user="$DB_USER" \
        --password="$DB_PASS" \
        --no-tablespaces \
        --single-transaction \
        --quick \
        "$DB_NAME" 2>/tmp/mysql_dump_err.log | gzip > "$BACKUP_FILE"

    DUMP_EXIT_CODE=${PIPESTATUS[0]}

    if [ $DUMP_EXIT_CODE -eq 0 ]; then
        log_msg "Backup via mysqldump created successfully"
        # Очищаем временный лог ошибок, если все прошло хорошо
        > /tmp/mysql_dump_err.log
    else
        log_msg "ERROR: mysqldump failed with exit code $DUMP_EXIT_CODE"
        # Можно добавить вывод содержимого /tmp/mysql_dump_err.log сюда для отладки
        cat /tmp/mysql_dump_err.log | while read line; do log_msg "DB_ERR: $line"; done
        exit 1
    fi
fi

# --- Удаление старых бэкапов ---
# Считаем количество удаленных файлов для отчета
DELETED_COUNT=$(find "$BACKUP_DIR" -name "db_*.sql.gz" -type f -mtime +$RETENTION_DAYS -print -delete | wc -l)
log_msg "Old backups deleted: $DELETED_COUNT files (older than $RETENTION_DAYS days)"