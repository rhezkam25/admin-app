#!/bin/sh
set -eu

BACKUP_DIR="${BACKUP_DIR:-/backup}"
STAMP="$(date +%Y%m%d-%H%M%S)"
DB_NAME="${DB_DATABASE:-admin_app}"
DB_USER="${DB_USERNAME:-admin_app}"
DB_PASS="${DB_PASSWORD:?DB_PASSWORD wajib diisi}"
DB_HOST="${DB_HOST:-db}"

mkdir -p "${BACKUP_DIR}"

mysqldump \
    --host="${DB_HOST}" \
    --user="${DB_USER}" \
    --password="${DB_PASS}" \
    --single-transaction \
    --routines \
    --triggers \
    "${DB_NAME}" | gzip > "${BACKUP_DIR}/db-${STAMP}.sql.gz"

tar czf "${BACKUP_DIR}/storage-${STAMP}.tar.gz" \
    -C /var/www/html \
    storage/app

sha256sum "${BACKUP_DIR}/db-${STAMP}.sql.gz" "${BACKUP_DIR}/storage-${STAMP}.tar.gz" \
    > "${BACKUP_DIR}/checksum-${STAMP}.txt"

echo "Backup selesai: ${BACKUP_DIR}/db-${STAMP}.sql.gz dan storage-${STAMP}.tar.gz"
