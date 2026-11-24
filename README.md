# API Polo Norte — Escenario B (Establo de renos y mantenimiento del terreno)

## Cómo levantar el proyecto
1. Clonar el repositorio y entrar a la carpeta del proyecto.
2. Copiar `.env.example` a `.env` y configurar `METEO_SOAP_URL`, `METEO_TIMEOUT=3` y los tokens de prueba.
3. Instalar dependencias con `composer install` y generar la clave con `php artisan key:generate`.
4. Iniciar el servidor con `php artisan serve` (disponible en `http://127.0.0.1:8000`).

## Variables de entorno (`.env.example`)
- `METEO_SOAP_URL`: URL del servicio SOAP heredado (`MeteoPoloNorteService`).
- `METEO_TIMEOUT`: tiempo máximo de espera en segundos (`3`).
- `API_TOKEN_JEFE_ESTABLO`: token de prueba con rol `JEFE_ESTABLO`.
- `API_TOKEN_ELFO`: token de prueba con rol `ELFO`.

## Usuarios / tokens de prueba
- **Jefe de establo (`JEFE_ESTABLO`):** `Authorization: Bearer token-jefe-establo-123` (acceso a `POST/PUT/DELETE /api/renos` y `PATCH /api/renos/{id}/asignacion`).
- **Elfo (`ELFO`):** `Authorization: Bearer token-elfo-456` (provoca `403` en rutas protegidas).

## Arquitectura
La aplicación sigue una arquitectura en capas (`Rutas` -> `Controladores` -> `Servicios` -> `Repositorio/Datos`), con `VerificarRolEstablo` para seguridad (`401`/`403`), manejador global de excepciones en formato único JSON y `MeteoAdapter` aislando la comunicación SOAP/XML.

Diagrama de arquitectura: [docs/arquitectura.png](docs/arquitectura.png)

## Diseño de microservicios y fallo el 24 de diciembre a las 23:00
Si el Polo Norte separara el sistema en microservicios, definiría tres límites: **Servicio de Renos** (datos: renos, energía, estado de vuelo), **Servicio de Mantenimiento y Terreno** (datos: tareas por zona, fechas, estados) y **Servicio de Integración Meteorológica** (datos: pronósticos cacheados y estado de estación SOAP).

Si el servicio meteorológico o el servicio de mantenimiento caen el 24 de diciembre a las 23:00, el despegue no debe bloquearse por completo. Mi API ya aísla el SOAP mediante `MeteoAdapter` con timeout de 3 segundos: al fallar al programar en `PISTA_DESPEGUE`, registra el mantenimiento como `PENDIENTE_CLIMA` y responde `202 Accepted` para conciliarlo después. Entre microservicios se usarían colas asincrónicas y caché local de última lectura válida, evitando fallos en cascada durante la operación crítica de Nochebuena.