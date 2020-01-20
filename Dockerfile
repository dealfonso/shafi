from ubuntu
ARG DEBIAN_FRONTEND=noninteractive
ENV TZ=Europe/Madrid
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
run apt update && apt -y dist-upgrade && apt install -y apache2 php php-mysql mysql-server && mkdir -p /var/lib/mysql /var/run/mysqld && chown -R mysql:mysql /var/lib/mysql /var/run/mysqld
add . /var/www/html/shafi
add bin/startindocker /
cmd /startindocker
