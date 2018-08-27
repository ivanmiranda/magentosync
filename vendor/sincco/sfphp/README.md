![saltamontes](https://github.com/sincco/sfphp/blob/master/saltamontes_min.png)
# Saltamontes (sfphp)
#### Simple Framework (for) PHP

[![Latest version][ico-version]][link-packagist]

Simplificando al máximo las características de todo framework para PHP, este permite ser un punto de entrada
para quienes aún no están utilizando alguno, o desean entender cada elemento que lo componen.

Haciendo uso de patrones singleton y una implementación sencilla y en el manejo de vistas, Sfphp, te puede ayudar a desarrollar una aplicación en un menor tiempo y organizando tus códigos de mejor manera.

### Instalación
Integra a tu aplicación con Composer
```
{
    "repositories": [
      {
        "type": "vcs",
        "url": "https://github.com/sincco/sfphp"
      }
    ],
    "require": {
        "sincco/sfphp": "dev-master",
        "desarrolla2/cache":  "~2.0",
        "twig/twig":  "~1.0"
    }
}
```

### Creación de app
Esto dejará una nueva estructura de directorios dónde comenzarás a alojar tu aplicación
```
App
|-Core
|-Controladores
|-|-Inicio.php
|-Modelos
|-Vistas
Etc
|-Cache
|-Config
|-|-config.xml
|-Logs
|-Sesiones
Libs
```
##### O
Clona el branch skeleton, con una app sencilla de ejemplo

## Configuracion

### General de la APP
```
<app>
    <key>la clave generada por el script iniciar.php o que puedes obtener desde el script llave.php</key>
    <name>nombre de la app</name>
    <company>sincco</company>
        <cache>segundos</cache>
        <eemailapi>API de ElasticMail</eemailapi>
</app>
```
### Front end
```
<front>
    <url>http://tudominio.com/</url>
</front>
```
### Bases de datos
Sólo puedes tener una por default (a la que se conectan todos los modelos por defecto), pero tantas como necesites con un identificador distinto
```
<bases>
    <default>
        <host></host>
        <user></user>
        <password>encriptado con la llave de la app, puedes usar en script encriptar.php?s=password</password>
        <dbname></dbname>
        <type>firebird|mysql|sqlsrv|otro</type>
    </default>
</bases>
```
### Opciones de desarrollo
```
<dev>
    <log>1|0 activa el log del framework (en Etc/Logs)</log>
    <showerrors>1|0 muestra los errores propios del framework en pantalla</showerrors>
    <showphperrors>1|0 muestra los errores de php</showphperrors>
        <querylog>1|0 activa un log de querys ejecutados</querylog>
        <contact>correo de contacto para soporte</contact>
</dev>
```

### Base de datos

#### CRUD

```
$model->empresas()
    ->where( 'estatus', 'Activa' )
    ->where( 'empresa', '01' )
    ->join( 'usuariosEmpresas usr', 'usr.empresa = maintable.empresa' )
    ->order( 'razonSocial' );
Debug::log($model->getCollection());
```


### Cache


### Peticiones
Cada petición se procesa seccionando la URL del siguiente modo:
```
dominio.com/[modulo]/controlador/accion/[(parametro1/valor1)...(parametron/valorn]
```
El controlador y accion por defecto son 'inicio', por lo que, al no recibirse dentro de la URL, estos son los que se ejecutarán de forma inmediata, así que deben existir dentro de tu estructura de archivos.

##### Parametros get
Los parametros se procesan de forma lineal, por lo que, si no llevan el orden correcto, no podrás encontrar el valor que necesitas. 
```
.../fruta/manzana/color/rojo
```
Se traduce en el siguiente arreglo:
```
(['fruta'] => ['manzana']) ,
(['color'] => ['rojo']) 
```
Mientras que, si se rompe el patrón:
```
.../fruta/color/rojo
```
se obtiene:
```
(['fruta'] => ['color']) ,
(['rojo'] => NULL) 
```

### Log de errores
Ejecutando el script error_notification.php se envia un correo a la direccion configurada con el log actual de errores.

### Nginx
Configuración básica para un vhost en Nginx
```
server {
        listen       80;
        listen       [::]:80;
        server_name sitio;
        index index.php;
        client_max_body_size 2M;
        root /var/www/html/sitio;
        proxy_set_header    Host              $host;
        proxy_set_header    X-Real-IP         $remote_addr;
        proxy_set_header    X-Forwarded-For   $proxy_add_x_forwarded_for;

        # Don't serve hidden files.
        location ~ /\. {
                deny all;
        }

        location = /favicon.ico {
                log_not_found off;
        }

        location / {
                try_files $uri /index.php?$args;
        }

        location ~ \.php$ {
                fastcgi_pass unix:/var/run/php5-fpm.sock;
                fastcgi_index index.php;
                include fastcgi_params;

                #Activar CACHE PHP
                fastcgi_split_path_info ^(.+\.php)(/.+)$;
                fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
                fastcgi_intercept_errors on;
                fastcgi_ignore_client_abort off;
                fastcgi_connect_timeout 60;
                fastcgi_send_timeout 90;
                fastcgi_read_timeout 90;
                fastcgi_buffer_size 128k;
                fastcgi_buffers 4 256k;
                fastcgi_busy_buffers_size 256k;
                fastcgi_cache CZONE;
                fastcgi_cache_valid   200 302  1h;
                fastcgi_cache_valid   301 1h;
                fastcgi_cache_min_uses  2;
        }

        #Para Framewok de PHP
        if (!-e $request_filename){
                rewrite ^(.+)$ /index.php?url=$1 break;
        }

        error_log /var/www/__logs/error error;
}
```
#### NOTICE OF LICENSE
This source file is subject to the Open Software License (OSL 3.0) that is available through the world-wide-web at this URL:
http://opensource.org/licenses/osl-3.0.php

**Happy coding!**
- [ivan miranda](http://ivanmiranda.me)