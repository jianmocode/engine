server {
    listen 127.0.0.1:80;
    server_name  localhost.xpmapp.com;
    resolver 223.5.5.5 223.6.6.6 1.2.4.8 114.114.114.114 valid=3600s;
    root /apps; 
    access_log  /logs/web/access.apps.log;
    error_log  /logs/web/error.apps.log;
    client_max_body_size 256m;
    index index.html index.php;

    location ~ \.php$ {
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php7-fpm.sock;
        fastcgi_index index.php;
        fastcgi_read_timeout 86400;
        fastcgi_split_path_info ^(.+\.php)(.*)$;
        include fastcgi_params;
        try_files $uri =404;
	}
}
