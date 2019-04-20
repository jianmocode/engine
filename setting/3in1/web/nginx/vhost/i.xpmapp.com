server {
    listen 80;
    server_name i.xpmapp.com;
    root /code;
    access_log  /logs/web/access.log;
    error_log  /logs/web/error.log;

    proxy_connect_timeout      86400;
    proxy_send_timeout         86400;
    proxy_read_timeout         86400;
    send_timeout               86400; 
    keepalive_timeout          86400;


    location /static-file/ {  
       alias /data/stor/public/;
       access_log  /logs/web/access.static.log;
       error_log  /logs/web/error.static.log;
    }

    location /static/ {  
       alias /code/static/;
       access_log  /logs/web/access.core.static.log;
       error_log   /logs/web/error.core.static.log;
    }

    location /ws-server/ {
        proxy_pass http://127.0.0.1:10086;
        proxy_read_timeout 86400s;
        proxy_send_timeout 86400s;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }

    location / {
        include /code/route.rewrite.conf;
    }
    
    client_max_body_size 256m;
    index index.php;
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