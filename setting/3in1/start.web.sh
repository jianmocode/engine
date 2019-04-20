#!/bin/sh
CONF=$CONF
RENEW=$RENEW
MAIN_HOST=$HOST
HTTPS=$HTTPS
USER=$USER
GROUP=$GROUP
WORKER=$WORKER
MYSQL=$1
REPAIR=$2
LOG="/logs/web/shell.log"
WSLOG="/logs/web/ws-server.log"

if [ -z $USER ]; then
	USER="www-data"
fi

if [ -z $GROUP ]; then
	GROUP="www-data"
fi

if [ -z $HTTPS ]; then
	HTTPS="ON"
fi

if [ ! -d "/logs/web" ]; then
	mkdir -p /logs/web
fi

if [ ! -d "/config/web" ]; then
	mkdir -p /config/web
fi

# 配置目录
if [ ! -d "/config/jianmo" ]; then
	mkdir -p /config/jianmo
fi

# 应用目录
if [ ! -d "/apps" ]; then
	mkdir -p /apps
    chown -R $USER:$GROUP /apps
fi

# 检出代码
if [ ! -d "/code" ]; then
	/usr/bin/git clone --single-branch --branch publish https://github.com/trheyi/xpmse.git /code
fi

# 重新检出代码
if [ ! -f "/code/README.md" ]; then
    rm -rf /code
	/usr/bin/git clone --single-branch --branch publish https://github.com/trheyi/xpmse.git /code
fi

if [ ! -f "/bin/xpm" ]; then
    ln -s /code/bin/xpm.phar /bin/xpm
    chmod +x /code/bin/xpm.phar
    chmod +x /code/service/bin/que
fi


if [ ! -f "/bin/phpunit" ]; then
    ln -s /composer/vendor/bin/phpunit /bin/phpunit
    chmod +x /composer/vendor/bin/phpunit
fi


# 检查用户组
ug=$(ls -l /config/web | awk '{print $3":"$4}')
if [ "$ug" != "root:root" ]; then
	chown -R root:root /config/web 
fi

ug=$(ls -l /logs/web  | awk '{print $3":"$4}')
if [ "$ug" != "root:root" ]; then
	chown -R root:root /logs/web  
fi

ug=$(ls -l /config/jianmo | awk '{print $3":"$4}')
if [ "$ug" != "$USER:$GROUP" ]; then
	chown -R $USER:$GROUP /config/jianmo
fi

ug=$(ls -l /code/config | awk '{print $3":"$4}')
if [ "$ug" != "$USER:$GROUP" ]; then
	chown -R $USER:$GROUP /code/config
fi

ug=$(ls -l /code/upload | awk '{print $3":"$4}')
if [ "$ug" != "$USER:$GROUP" ]; then
	chown -R $USER:$GROUP /code/upload
fi

ug=$(ls -l /apps | awk '{print $3":"$4}')
if [ "$ug" != "$USER:$GROUP" ]; then
	if [ "$REPAIR" = "on" ]; then
		chown -R $USER:$GROUP /apps
	fi
fi



# ug=$(ls -l /composer | awk '{print $3":"$4}')
# if [ "$ug" != "$USER:$GROUP" ]; then
# 	chown -R $USER:$GROUP /composer
# fi


# 更新配置文件
if [ -z $CONF ]; then
	CONF="default"
fi

if [ -z $MAIN_HOST ]; then
	MAIN_HOST="i.xpmapp.com"
fi

if [ ! -d "/defaults/web/$CONF" ]; then
	CONF="default"
	echo "$CONF not exist. default selected!" >> $LOG
fi

if [ ! -d "/config/web/nginx" ]; then
	cp -r "/defaults/web/$CONF/nginx" /config/web/
	sed -i "s/user www-data/user $USER $GROUP/g" /config/web/nginx/nginx.conf
fi

if [ ! -d "/config/web/php" ]; then
	cp -R "/defaults/web/$CONF/php" /config/web/
	sed -i "s/user = www-data/user = $USER/g"  /config/web/php/fpm/php-fpm.d/www.conf
	sed -i "s/group = www-data/group = $GROUP/g" /config/web/php/fpm/php-fpm.d/www.conf
	sed -i "s/listen.owner = www-data/listen.owner = $GROUP/g" /config/web/php/fpm/php-fpm.d/www.conf
	sed -i "s/listen.group = www-data/listen.group = $GROUP/g" /config/web/php/fpm/php-fpm.d/www.conf
fi

if [ ! -d "/config/web/lua" ]; then
	cp -R "/defaults/web/$CONF/lua" /config/web/
fi

if [ ! -d "/config/crt" ]; then
	cp -R "/defaults/web/$CONF/crt" /config/
fi

# + 证书目录
if [ ! -d "/data/crt" ]; then
	mkdir /data/crt
fi

ug=$(ls -l /data/crt | awk '{print $3":"$4}')
if [ "$ug" != "$USER:$GROUP" ]; then
	chown -R $USER:$GROUP /data/crt
fi


# copy php.ini 
if [ -f "/config/web/php/php.ini" ]; then
	cp -f /config/web/php/php.ini  /opt/php7/etc/php.ini
fi

# copy route.lua
if [ ! -f "/code/route.lua" ]; then
	cp -f /config/web/lua/route.lua  /code/route.lua
fi

# copy route.rewrite.conf
if [ ! -f "/code/route.rewrite.conf" ]; then
	cp -f /config/web/nginx/route.rewrite.conf  /code/route.rewrite.conf
fi


# 创建默认目录
if [ ! -d "/run/nginx" ]; then
	mkdir /run/nginx
fi

if [ ! -d "/run/php" ]; then
	mkdir -p /run/php/fpm
fi

if [ ! -d "/logs/php" ]; then
	mkdir -p /logs/php/fpm
fi

if [ ! -d "/logs/nginx" ]; then
	mkdir -p /logs/nginx/fpm
fi

if [ ! -d "/data/stor/public" ]; then
	mkdir -p /data/stor/public
	chown -R $USER:$GROUP /data/stor
fi

if [ ! -d "/data/stor/private" ]; then
	mkdir -p /data/stor/private
	chown -R $USER:$GROUP /data/stor
fi

if [ ! -d "/data/composer" ]; then
	mkdir -p /data/composer
	chown -R $USER:$GROUP /data/composer
fi

ug=$(ls -l /config/crt | awk '{print $3":"$4}')
if [ "$ug" != "$USER:$GROUP" ]; then
	chown -R $USER:$GROUP /config/crt
fi

# XPM HOST 
echo "127.0.0.1 localhost.xpmapp.com" >> /etc/hosts

# XpmSE命令
chmod +x /code/bin/xpm.phar

# 等待 MySQL 进程
if [ "$MYSQL" = "on" ]; then

	retry=0
	while !(/usr/bin/mysqladmin ping)
	do
	   echo "waiting for mysql ... $retry " 
	   retry=`expr $retry + 1`
	   	if [ "$retry" -eq "30" ];then
	   		break
	 	fi
	   sleep 1
	done

	# 创建默认用户
	DB_USER=$(/usr/bin/mysql -e "SELECT User FROM mysql.user;" |grep xpmse)
	if [ "$DB_USER" != "xpmse" ]; then
		# 创建默认数据库用户名和密码
		/usr/bin/mysql -e  "CREATE DATABASE IF NOT EXISTS xpmse DEFAULT CHARSET utf8 COLLATE utf8_general_ci;" >> $LOG 2>&1 
		/usr/bin/mysql -e  "CREATE USER 'xpmse'@'%' IDENTIFIED BY 'xpmse';"  >> $LOG 2>&1 
		/usr/bin/mysql -e  "GRANT ALL ON xpmse.* TO 'xpmse'@'%'; " >> $LOG 2>&1 
		/usr/bin/mysql -e  "flush privileges;" >> $LOG 2>&1 
	fi

fi


# 启动 PHP-FPM
/opt/php7/sbin/php-fpm -c /config/web/php/php.ini  -y /config/web/php/fpm/php-fpm.conf >> $LOG 2>&1 &

# 启动 Nginx
/opt/openresty/nginx/sbin/nginx -c /config/web/nginx/nginx.conf >> $LOG 2>&1 &

# 启动系统服务
/bin/su -s /bin/sh www-data -c 'PATH=$PATH:/opt/php7/bin /bin/xpm boot' >> $LOG 2>&1