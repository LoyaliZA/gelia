#!/bin/bash
#./refresh.sh
# refresh.sh - Script de actualización rápida para producción GELIA

echo "🚀 Iniciando actualización del sistema GELIA..."

echo "Descargando últimos cambios del repositorio..."
git pull origin main

# 1. Compilar assets de frontend (Vite)
echo "📦 Compilando módulos JS y CSS..."
docker compose -f compose.prod.yaml exec gelia npm run build

# 2. Limpiar caché profundo de Laravel
echo "🧹 Limpiando caché (Config, Vistas, Rutas)..."
docker compose -f compose.prod.yaml exec gelia php artisan optimize:clear

# 3. Reconstruir caché optimizado
echo "⚡ Generando caché de producción..."
docker compose -f compose.prod.yaml exec gelia php artisan optimize

echo "✅ Actualización completada con éxito."