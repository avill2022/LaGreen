# Gestor de Fases de Plantas — Versión Web

<img width="878" height="899" alt="image" src="https://github.com/user-attachments/assets/d2354cd4-0c1d-4ab4-9fcd-c9af8c2d83ea" />

Aplicación web en PHP (sin framework) para gestionar el seguimiento de plantas en germinación y visualizar sus calendarios de crecimiento.

Reimplementa la funcionalidad **Seguimiento** y **Calendario** de la aplicación de escritorio original (Python/customtkinter), usando la misma base de datos SQLite (`plants.db`).

## Funcionalidades

- **Seguimiento**: registrar plantas que han germinado y monitorizar su fase actual.
  - Fase actual coloreada y barra de progreso.
  - Secuencia de fases (`germinación → plántula → vegetativa → …`) con la fase activa marcada.
  - Chips de cuidados de la
 fase activa: luz, agua, pH, temperatura y humedad.
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
- Archivos `example_plants.json` y `data/hortalizas.json` presentes (obligatorio para la carga inicial y el calendario de siembra)

> ⚠️ **Producción**: si al desplegar el proyecto falta `example_plants.json`, la primera visita **no mostrará ningún error** pero el desplegable de plantas saldrá vacío (la importación inicial se omite silenciosamente). Asegúrate de que `example_plants.json` y la carpeta `data/` se copien al servidor.

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

### 2. Situarse en la raíz del proyecto

```bash
cd /ruta/al/proyecto
```

### 3. Poblar la base de datos (opcional)

La base de datos se crea automáticamente al abrir la aplicación. La primera vez, si no existe ninguna planta, se importan automáticamente los datos de ejemplo de `example_plants.json` (23 plantas).

Para importar manualmente (CLI):

```bash
php seed.php
```

### 4. Comprobar el despliegue (diagnóstico)

Ejecuta `check.php` (web o CLI) para verificar extensión SQLite, presencia de los `.json`, permisos de `plants.db` y el número de plantas:

```bash
php check.php
```

> El archivo `plants.db` se crea en la raíz del proyecto (junto a `example_plants.json`). Es el mismo archivo que usa la versión de escritorio Python.

## Ejecución

### Servidor integrado de PHP (desarrollo)

Desde la raíz del proyecto:

```bash
php -S localhost:8000
```

Abre en el navegador: http://localhost:8000

### Apache o Nginx (producción)

Apunta el DocumentRoot de tu virtual host a la raíz del proyecto. El index se resuelve automáticamente en `index.php`.

**Requisito de despliegue**: copia también `example_plants.json` y la carpeta `data/` (no solo los `.php`). Si despliegas desde Git, ten en cuenta que `plants.db` está en `.gitignore` y no se transfiere; se crea y rellena solo en la primera visita, siempre que `example_plants.json` esté presente y el directorio sea escribible por el usuario del servidor web.

## Uso

La página principal tiene cuatro pestañas:

1. **Seguimiento** *(requiere cuenta)* — Añade una planta en germinación seleccionando su tipo, nombre, fecha de germinación y notas. Cada tarjeta muestra la fase actual, progreso, cuidados de la fase y cosecha estimada. Puedes eliminar plantas del seguimiento con el botón **Eliminar**.
2. **Calendario** *(requiere cuenta)* — Visualiza la línea de tiempo de todas las plantas en seguimiento, con las fases coloreadas por mes.
3. **Calendario de siembra** — Guía pública de siembra según el mes.
4. **Calculadora de crecimiento** — Calculadora pública de fases.

La primera vez, regístrate con **Registrarse** en la cabecera. Después usa **Iniciar sesión** para acceder a **Seguimiento** y **Calendario**.

## Estructura del proyecto

```
/
├── index.php               # Front controller (router MVC, arranque, sesión y enrutado por ?tab=)
├── config.php              # Configuración (rutas y constantes) + autoloader de App\
├── functions.php           # Lógica de fases, cosecha, colores y helpers CSRF
├── app/                    # Núcleo MVC
│   ├── Core/
│   │   ├── Controller.php  # Controlador base (render, layout y helpers)
│   │   └── Router.php      # Mapea cada pestaña a su controlador
│   ├── Controllers/
│   │   ├── AuthController.php        # login / registro / logout
│   │   ├── SeguimientoController.php # seguimiento (listar, añadir, eliminar)
│   │   ├── CalendarioController.php  # calendario Gantt
│   │   ├── SiembraController.php     # calendario de siembra
│   │   ├── CalculadoraController.php # calculadora de crecimiento
│   │   └── DetalleController.php     # ficha de cultivo (?tab=detalle&id=…)
│   └── Models/
│       ├── Database.php    # Capa de datos PDO/SQLite (plantas, fases y usuarios)
│       └── Catalog.php     # Catálogo por categorías desde data/*.json
├── seed.php                # Importador CLI de example_plants.json
├── check.php               # Diagnóstico de despliegue (eliminar o restringir en producción)
├── example_plants.json     # Datos de ejemplo (obligatorio para la carga inicial)
├── data/                   # Catálogos por categoría (obligatorio)
│   ├── hortalizas.json     # Ficha de cultivo de hortalizas (categoría 'hortalizas')
│   └── cactus.json, cotton.json, example_fruts.json, flowers.json
├── plants.db               # Base SQLite creada automáticamente (no está en Git)
├── views/                  # Plantillas MV (solo reciben datos preparados)
│   ├── layout.php          # Layout compartido (cabecera, navegación y pie)
│   ├── login.php           # Vista de inicio de sesión
│   ├── register.php        # Vista de registro
│   ├── seguimiento.php     # Vista de seguimiento
│   ├── calendario.php      # Vista de calendario (Gantt)
│   ├── siembra.php         # Vista de calendario de siembra
│   ├── calculadora.php     # Vista de calculadora de crecimiento
│   └── detalle.php         # Vista de ficha de cultivo
└── assets/
    ├── style.css           # Estilos
    └── vendor/
        └── html2canvas.min.js  # Librería de exportación de imagen (MIT)
```

## Datos

Los datos se almacenan en una base SQLite local (`plants.db`) creada automáticamente en la raíz del proyecto. Es el mismo esquema y archivo que usa la versión de escritorio, por lo que ambas aplicaciones comparten los datos.

El catálogo de siembra se organiza por categorías en `data/*.json`. Cada archivo es una categoría (por ejemplo el archivo `data/hortalizas.json` contiene el catálogo `hortalizas`). El modelo `Catalog` los lee de forma uniforme, tanto en formato envuelto (`{"hortalizas": [...]}`) como en lista pura.

Cada tarjeta del calendario de siembra y el botón **Ver ficha completa** enlazan a la ficha de la planta en `index.php?tab=detalle&id=HOR-001`.

Para restablecer los datos de ejemplo:

```bash
# 1. Detén el servidor web
# 2. Elimina la base de datos (perderás los datos propios)
rm plants.db
# 3. Vuelve a iniciar y la app reimportará example_plants.json automáticamente
```
