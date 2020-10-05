# drop
 A simple file sharing service

Preview: https://drop.cutiepie.at/

## Usage
1. Clone this repository
2. Add the following to your VirtualHost:
```
        <Directory />
                Options FollowSymLinks
                AllowOverride None
        </Directory>
        <Directory /var/www/(replace with document root of this repository!)>
                Options +FollowSymLinks +MultiViews
                AllowOverride None
                Order allow,deny
                allow from all
                RewriteEngine on
                RewriteBase /
                RewriteCond %{REQUEST_URI} !^/((index\.php)|(style\.css)|(hightlight\.css)|(script\.js)|(new\.png)|(download\.png)|(favicon\.png)|(\.well-known)|(icon/.*)|(d/.*))$
                RewriteRule "^(.+)$" /index.php?f=$1
                RewriteCond %{REQUEST_URI} ^/d/.*$
                RewriteRule "^d/(.+)$" /index.php?d=$1
         </Directory>
```
