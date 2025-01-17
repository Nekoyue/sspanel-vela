FROM php:8.2-fpm
# Install PHP extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions bcmath curl fileinfo json mbstring mysqli openssl pdo_mysql posix redis xml yaml zip

RUN apt-get update && apt-get install -y netcat-openbsd

# Compose it
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html/
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN sed -i 's@^disable_functions.*@disable_functions = passthru,exec,system,chroot,chgrp,chown,shell_exec,proc_open,proc_get_status,ini_alter,ini_restore,dl,readlink,symlink,popepassthru,stream_socket_server,fsocket,popen@' /usr/local/etc/php-fpm.conf

RUN #sed -i 's/^listen = .*/listen = \/run\/php\/php8.2-fpm.sock/' /usr/local/etc/php-fpm.d/www.conf
RUN sed -i 's/^user = .*/user = root/' /usr/local/etc/php-fpm.d/www.conf
RUN sed -i 's/^group = .*/group = root/' /usr/local/etc/php-fpm.d/www.conf
#RUN sed -i 's/^;listen.owner = .*/listen.owner = root/' /usr/local/etc/php-fpm.d/www.conf
#RUN sed -i 's/^;listen.group = .*/listen.group = root/' /usr/local/etc/php-fpm.d/www.conf

COPY ./docker-sspanel-entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Run
ENTRYPOINT ["/entrypoint.sh"]
