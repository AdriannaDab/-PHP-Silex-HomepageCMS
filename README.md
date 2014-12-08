Install

1. File with database dump is in src/data.sql

2. Run php composer.phar install

3. Update information information required to connect to the database web/index.php

    'db.options' => array(
        'driver' => 'pdo_mysql',
        'host' => 'localhost',
        'dbname' => 'databasename',
        'user' => 'user',
        'password' => 'password',
        'charset' => 'utf8',
    ),

4. In web/ create file .htaccess:

<IfModule mod_rewrite.c>
    Options -MultiViews
    RewriteEngine On
    RewriteBase {YourPath}/web/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [QSA,L]
</IfModule>


5. Run chmod 777 web/media/

6. Login and password to admin account. You should change them after first login.

login:admin
password:admin123
 
