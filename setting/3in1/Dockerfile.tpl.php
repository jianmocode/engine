## ===========================================
#  Jianmo.ink  DockerFile
#  
#  Jianmo <?=$tag?> ( Build from <?=$ver?> )
#  
#  @Name jianmo/engine:<?=$tag?>
#  @By Robot <support@diancloud.com>
#  
#  USEAGE:
#  	  docker run -d \
#  	     -e "HOST=u.jianmoapp.cn" \
#  	     -e "HTTPS=FORCE"  \
#        -v /code:/code \
#		 -v /host/logs:/logs  \
#		 -v /host/data:/data  \
#		 -v /host/config:/config  \
#		 -v /host/apps:/apps  \
#		 -p 80:80 \
#		 jianmo/engine:<?=$tag?>
#  	 
#  FROM:
#  	 FROM jianmo/server:engine
#  	 
#  BUILD:
#     docker build -t jianmo/engine:<?=$tag?> .
#  	  
# ===========================================

FROM jianmo/server:engine
LABEL maintainer="JianMo <https://www.JianMo.ink>"

VOLUME ["/run","/data", "/apps", "/logs", "/config", "/code"]
ENV PATH=${PATH}:/opt/php7/bin:/opt/php7/sbin:/opt/openresty/bin:/opt/openresty/nginx/sbin \
	CONF=default \
	TERM=linux   \
	HOST=i.jianmoapp.com \
	_XPMSE_VERSION=<?=$tag?> \
	_XPMSE_REVISION=<?=$ver?> \
	_XPMSE_CONFIG_ROOT=/config/jianmo  \
	REDIS_HOST=redis-host \
	REDIS_PORT=6379 \
	REDIS_SOCKET= \
	REDIS_USER= \
	REDIS_PASS=

<?php foreach( $addmap as $name=>$addto ) : ?>
ADD <?=$name?> <?=$addto?> <?="\n"?>
<?php endforeach; ?>

RUN chmod +x /start.sh  && \
	chmod +x /start/*  && \
	chmod +x /backup.sh && \
	chmod +x /bin/composer  && \
	echo "Complete"

EXPOSE 80 443 3306 6379
CMD ["sh", "/start.sh"]