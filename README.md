# Gestor de Fases de Plantas — Versión Web

Aplicación web en PHP (sin framework) para gestionar el seguimiento de plantas en germinación y visualizar sus calendarios de crecimiento.

Reimplementa la funcionalidad **Seguimiento** y **Calendario** de la aplicación de escritorio original (Python/customtkinter), usando la misma base de datos SQLite (`plants.db`).

## Funcionalidades

- **Seguimiento**: registrar plantas que han germinado y monitorizar su fase actual.
  - Fase actual coloreada y barra de progreso.
  - Secuencia de fases (`germinación → plántula → vegetativa → …`) con la fase activa marcada.
  - Chips de cuidados de la fase activa: luz, agua, pH, temperatura y humedad.
  - Fecha estimada de cosecha con días restantes.
- **Calendario**: vista Gantt anual con todas las plantas en seguimiento.
  - Bloques de color por fase de crecimiento y duración.
  - Encabezados de mes y marcador "Hoy".
- **Usuarios**: registro e inicio de sesión.
  - **Seguimiento** y **Calendario** requieren una cuenta (las pestañas **Calendario de siembra** y **Calculadora de crecimiento** son públicas).
  - Las contraseñas se guardan con `password_hash()` (bcrypt) y las sesiones usan protección CSRF.
- **Calculadora de crecimiento**: guía por fases con fechas, duración y cuidados de cada fase.
  - Botón **Imprimir / PDF** (abre el diálogo de impresión del navegador → "Guardar como PDF").
  - Botón **Exportar imagen** (genera un PNG de la guía mediante `html2canvas`, incluido localmente en `assets/vendor/`).

## Requisitos

- PHP 8.0+ (probado con PHP 8.5)
- Extensión `pdo_sqlite` (SQLite para PHP)
- Permisos de escritura en el directorio del proyecto (para crear/actualizar `plants.db`)

## Instalación

### 1. Instalar la extensión SQLite (obligatorio)

En Debian/Ubuntu:

```bash
sudo apt-get install php8.5-sqlite3
```

Reemplaza `8.5` por la versión de PHP instalada si es distinta. Comprueba que está activa:

```bash
php -m | grep -i sqlite
```

Debe mostrar `pdo_sqlite` (y opcionalmente `sqlite3`).

### 2. Navegar al proyecto

```bash
cd web
```

### 3. Poblar la base de datos (opcional)

La base de datos se crea automáticamente al abrir la aplicación. La primera vez, si no existe ninguna planta, se importan automáticamente los datos de ejemplo de `../example_plants.json` (23 plantas).

Para importar manualmente (CLI):

```bash
php seed.php
```

> El archivo `plants.db` se crea en la raíz del proyecto (junto a `example_plants.json`), no dentro de `web/`. Es el mismo archivo que usa la versión de escritorio Python.

## Ejecución

### Servidor integrado de PHP (desarrollo)

Desde la carpeta `web`:

```bash
php -S localhost:8000
```

Abre en el navegador: http://localhost:8000

### Apache o Nginx (producción)

Apunta el DocumentRoot de tu virtual host a la carpeta `web/`. El index se resuelve automáticamente en `index.php`.

## Uso

La página principal tiene cuatro pestañas:

1. **Seguimiento** *(requiere cuenta)* — Añade una planta en germinación seleccionando su tipo, nombre, fecha de germinación y notas. Cada tarjeta muestra la fase actual, progreso, cuidados de la fase y cosecha estimada. Puedes eliminar plantas del seguimiento con el botón **Eliminar**.
2. **Calendario** *(requiere cuenta)* — Visualiza la línea de tiempo de todas las plantas en seguimiento, con las fases coloreadas por mes.
3. **Calendario de siembra** — Guía pública de siembra según el mes.
4. **Calculadora de crecimiento** — Calculadora pública de fases.

La primera vez, regístrate con **Registrarse** en la cabecera. Después usa **Iniciar sesión** para acceder a **Seguimiento** y **Calendario**.

## Estructura del proyecto

```
web/
├── index.php               # Página principal (pestañas, sesión y controladores)
├── config.php              # Configuración (rutas y constantes)
├── Database.php            # Capa de datos con PDO/SQLite (plantas, fases y usuarios)
├── functions.php           # Lógica de fases, cosecha, colores y helpers CSRF
├── seed.php                # Importador CLI de example_plants.json
├── views/
│   ├── login.php           # Vista de inicio de sesión
│   ├── register.php        # Vista de registro
│   ├── seguimiento.php     # Vista de seguimiento
│   ├── calendario.php      # Vista de calendario (Gantt)
│   ├── siembra.php         # Vista de calendario de siembra
│   └── calculadora.php     # Vista de calculadora de crecimiento
└── assets/
    ├── style.css           # Estilos
    └── vendor/
        └── html2canvas.min.js  # Librería de exportación de imagen (MIT)
```

## Datos

Los datos se almacenan en una base SQLite local (`plants.db`) creada automáticamente en la raíz del proyecto. Es el mismo esquema y archivo que usa la versión de escritorio, por lo que ambas aplicaciones comparten los datos.

Para restablecer los datos de ejemplo:

```bash
# 1. Detén el servidor web
# 2. Elimina la base de datos (perderás los datos propios)
rm ../plants.db
# 3. Vuelve a iniciar y la app reimportará example_plants.json automáticamente
```
