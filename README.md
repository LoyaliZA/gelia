# 🚀 G.E.L.I.A. v2.3
**Generador de Listas Inteligentes y Automatizadas**

G.E.L.I.A. es un sistema web robusto diseñado para procesar, cruzar y limpiar grandes volúmenes de datos provenientes de reportes de ERP (Wizerp). Automatiza la creación de listas de resurtido, costos, inventarios y clientes, ahorrando horas de trabajo manual mediante el uso de fórmulas predefinidas y configuraciones personalizables.

---

## ✨ Características Principales

- 📊 **Listas Predeterminadas:** Generación a un clic de listas estándar (Resurtido, Costos, Actualizada, Inventario Bellaroma).
- 🛠️ **Listas Personalizadas (CRUD):** Creación, edición y borrado lógico de plantillas personalizadas. Permite elegir columnas, orden, esquema de colores y archivos obligatorios.
- 🧮 **Motor de Fórmulas Dinámico:** Cálculo automático de precios derivados (Plataformas, Lista 3, Lista 4, Lista Boutique y Costos Calculados).
- 📦 **Filtro Inteligente de Existencias:** Capacidad de omitir automáticamente productos sin stock físico (Stock = 0).
- 🧹 **Limpieza de Base de Datos:** Módulo dedicado para la normalización y limpieza de catálogos de Clientes.
- 📝 **Sistema de Logs:** Registro silencioso de actividad (creación, edición y eliminación de listas) para auditoría.
- 🎨 **Interfaz Moderna:** Diseño responsivo, Dark Mode nativo, Drag & Drop para archivos y alertas asíncronas (Toasts), construido con Tailwind CSS.

---

## 💻 Stack Tecnológico

- **Backend:** PHP 8.x / Laravel 11
- **Base de Datos:** MySQL 8
- **Procesamiento de Excel:** Rap2hpoutre/FastExcel
- **Frontend:** HTML5, JS Vanilla, Tailwind CSS
- **Infraestructura:** Docker, Laravel Sail, Ubuntu Server

---

## ⚙️ Despliegue y Comandos Útiles

El proyecto está dockerizado para garantizar consistencia entre los entornos de desarrollo y producción.

### Entorno de Desarrollo (Local)
Utiliza Laravel Sail para levantar el entorno sin configuraciones complejas.

```bash
# Levantar los contenedores en segundo plano
./vendor/bin/sail up -d

# Ejecutar migraciones de base de datos
./vendor/bin/sail artisan migrate

# Apagar los contenedores
./vendor/bin/sail down
