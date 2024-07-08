FROM php:8.2-cli
RUN apt-get update && docker-php-ext-install pdo pdo_mysql
WORKDIR /flights/src
ENTRYPOINT ["php", "-f", "main.php", "--"]