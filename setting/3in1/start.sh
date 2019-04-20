#!/bin/sh
USER=$USER
GROUP=$GROUP
if [ -z $USER ]; then
	USER="www-data"
fi

if [ -z $GROUP ]; then
	GROUP="www-data"
fi

groupadd $GROUP
useradd -g $GROUP $USER

if [ -z $REDIS ]; then
	REDIS="on"
fi

if [ -z $MYSQL ]; then
	MYSQL="on"
fi

if [ -z $REPAIR ]; then
	REPAIR="off"
fi

if [ "$REDIS" = "on" ]; then
	/start/redis.sh 
fi

if [ "$MYSQL" = "on" ]; then
	/start/mysql.sh
fi

/start/web.sh $MYSQL $REPAIR
/usr/bin/tail -f /dev/null