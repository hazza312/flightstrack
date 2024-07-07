FROM php:apache 
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql pdo_mysql

ENV APACHE_DOCUMENT_ROOT /flights/src/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN echo "LoadModule authn_dbd_module /usr/lib/apache2/modules/mod_authn_dbd.so" >> /etc/apache2/httpd.conf
RUN echo "LoadModule authn_dbm_module /usr/lib/apache2/modules/mod_authn_dbm.so" >> /etc/apache2/httpd.conf
RUN a2enmod auth_basic authn_core authn_file authn_socache authz_core authz_groupfile authz_host authz_owner authz_user

COPY .env.template .env 
COPY .htpasswd.template .htpasswd
COPY src/public/.htaccess.template src/public/.htaccess
