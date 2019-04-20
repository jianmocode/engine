#!/bin/bash
# Cron: 每6小时备份一次（ 设定宿主机 Crontab 
# 1 */6 * * * /usr/bin/docker exec <容器名称> /backup.sh >> /dev/null
#
log=/data/backup/`date +%Y-%m`.log
dst=/data/backup/`date +%Y-%m-%d`
name=`date +%H%M%S`.sql
/bin/mkdir -p $dst
echo "备份 $dst/$name ..." >> "$log"
/usr/bin/mysqldump -uroot --all-databases  > "$dst/$name"
/bin/gzip "$dst/$name"
echo "... $dst/$name.gz 完成" >> "$log"
