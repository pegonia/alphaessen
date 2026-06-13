#!/bin/bash
# backup_db.sh - Backup-Skript für Alphaessen-Datenbank
# 
# Usage: ./scripts/backup_db.sh
# 
# Erstellt ein Backup der SQLite-Datenbank und speichert es im storage/backups-Verzeichnis

# Konfiguration
DB_PATH="/workspace/pegonia__alphaessen/storage/database/alphaessen.db"
BACKUP_DIR="/workspace/pegonia__alphaessen/storage/backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/alphaessen_backup_${DATE}.db"

# Verzeichnis erstellen, falls nicht vorhanden
mkdir -p "${BACKUP_DIR}"

# Backup erstellen
echo "Erstelle Backup der Datenbank..."
cp "${DB_PATH}" "${BACKUP_FILE}"

# Prüfen, ob Backup erfolgreich war
if [ $? -eq 0 ]; then
    echo "Backup erfolgreich erstellt: ${BACKUP_FILE}"
    
    # Alte Backups bereinigen (nur die letzten 30 behalten)
    echo "Bereinige alte Backups..."
    ls -t "${BACKUP_DIR}"/alphaessen_backup_*.db | tail -n +31 | xargs rm -f 2>/dev/null
    
    echo "Backup abgeschlossen."
else
    echo "Fehler: Backup konnte nicht erstellt werden."
    exit 1
fi
