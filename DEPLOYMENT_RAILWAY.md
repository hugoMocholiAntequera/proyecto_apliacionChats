# Guía de Despliegue en Railway

## Variables de Entorno Requeridas en Railway

Para que la aplicación funcione correctamente en Railway, debes configurar las siguientes variables de entorno en tu proyecto:

### 1. DATABASE_URL
La URL de conexión a la base de datos MySQL. Railway proporciona esta variable automáticamente si agregas un servicio de MySQL.

Formato:
```
DATABASE_URL=mysql://usuario:password@host:port/database?serverVersion=8&charset=utf8mb4
```

Ejemplo:
```
DATABASE_URL=mysql://root:password123@containers-us-west-123.railway.app:3306/railway?serverVersion=8&charset=utf8mb4
```

### 2. APP_SECRET
Una cadena secreta para la seguridad de Symfony. Genera una aleatoria con:
```bash
openssl rand -hex 32
```

### 3. APP_ENV
Debe estar configurado como `prod` en producción:
```
APP_ENV=prod
```

### 4. APP_DEBUG (opcional)
En producción debe ser `0`:
```
APP_DEBUG=0
```

## Pasos para Desplegar

### 1. Configurar Base de Datos

1. En Railway, agrega un nuevo servicio MySQL a tu proyecto
2. Railway generará automáticamente las credenciales y la variable `DATABASE_URL`
3. Verifica que la variable `DATABASE_URL` esté disponible en tu servicio web

### 2. Ejecutar Migraciones

El Dockerfile actualizado ahora ejecuta automáticamente:
- Las migraciones de la base de datos
- Los fixtures necesarios (incluyendo el Chat General)

Esto sucede cada vez que el contenedor se inicia.

### 3. Verificar el Despliegue

Una vez desplegado, puedes verificar el estado de la API:

#### Health Check
```bash
GET https://tu-app.up.railway.app/api/health
```

Respuesta esperada:
```json
{
  "success": true,
  "message": "API Health Check",
  "data": {
    "database": "connected",
    "chatGeneral": "exists",
    "timestamp": "2026-02-09 12:34:56"
  }
}
```

#### Test de Conexión
```bash
POST https://tu-app.up.railway.app/api/conexion
Content-Type: application/json

{
  "apikey": "a9F3kL2Qx7M8PZcR4eVYH6B5NwD1JmU0tS"
}
```

## Cambios Realizados

### 1. API Key como Constante
La API key ahora es una constante privada de clase en lugar de una variable de instancia:
```php
private const API_KEY = "a9F3kL2Qx7M8PZcR4eVYH6B5NwD1JmU0tS";
```

### 2. Manejo de Errores Mejorado
- Se agregó un Event Listener (`ApiExceptionListener`) que captura todas las excepciones en rutas `/api/*` y devuelve respuestas JSON en lugar de HTML
- Se mejoró el logging de errores en el controlador

### 3. Endpoint de Health Check
Se agregó un nuevo endpoint `/api/health` para verificar:
- Conexión a la base de datos
- Existencia del chat general
- Estado general de la aplicación

### 4. Inicialización Automática
El Dockerfile ahora ejecuta automáticamente:
- `doctrine:migrations:migrate` - Crea/actualiza las tablas
- `doctrine:fixtures:load` - Carga datos iniciales (Chat General)

## Resolución de Problemas

### Error 500 en los endpoints
Si los endpoints devuelven error 500:
1. Verifica que `DATABASE_URL` esté correctamente configurada
2. Verifica los logs de Railway para ver el error específico
3. Usa el endpoint `/api/health` para diagnóstico

### "Chat General no existe"
Si el health check muestra que el chat general no existe:
1. Ejecuta manualmente: `php bin/console doctrine:fixtures:load --append`
2. O redeploye la aplicación para que se ejecute automáticamente

### Errores de Conexión a Base de Datos
1. Verifica que el servicio MySQL esté activo en Railway
2. Verifica que `DATABASE_URL` tenga el formato correcto
3. Verifica que el host y puerto sean accesibles desde tu servicio web

## Seguridad

### Credenciales
- ❌ NO commitear credenciales hardcodeadas
- ✅ Usar variables de entorno de Railway
- ✅ El archivo `import_db.php` ahora usa variables de entorno

### API Key
La API key actual está en el código por simplicidad, pero para producción considera:
- Moverla a una variable de entorno
- Usar un sistema de API keys más robusto con base de datos
- Implementar autenticación JWT

## Comandos Útiles en Railway

### Ver Logs
```bash
railway logs
```

### Ejecutar Comandos en el Contenedor
```bash
railway run php bin/console <comando>
```

### Forzar Redespliegue
```bash
railway up
```
