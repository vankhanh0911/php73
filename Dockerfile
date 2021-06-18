FROM php:7.3.20-fpm
COPY source/composer.json /build/phpADSV7/lib/php/ZendFramework/2.2.5/zf2/
COPY source/etc /build/phpADSV7/etc/
COPY source/php.ini /usr/local/etc/php/
RUN apt-get update \
    && apt-get install -qy libxml2-dev unzip libaio1 libfreetype6-dev libjpeg62-turbo-dev libmcrypt-dev libmagickwand-dev libgearman-dev locales libzip-dev librdkafka-dev libicu-dev zlib1g-dev libpq-dev git libcurl4-openssl-dev vim netcat postgresql python-setuptools libpng-dev libjpeg-dev \
    && locale-gen C.UTF-8 \
    && /usr/sbin/update-locale LANG=C.UTF-8 \
    && apt-get autoremove -y \
    && apt-get clean all
RUN docker-php-ext-install soap pdo pgsql pdo_pgsql pdo_mysql mysqli intl opcache bcmath zip curl gd
RUN pecl install redis
RUN pecl install channel://pecl.php.net/rdkafka-beta
RUN pecl install gearman
RUN pecl install imagick
RUN rm -rf /tmp/pear
RUN docker-php-ext-enable redis
RUN docker-php-ext-enable imagick
RUN echo "extension=rdkafka.so" > /usr/local/etc/php/conf.d/rdkafka.ini
RUN echo "extension=gearman.so" > /usr/local/etc/php/conf.d/gearman.ini
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
    && chmod +x /usr/bin/composer

RUN cd /tmp && curl -L https://raw.github.com/adrianharabula/php7-with-oci8/master/instantclient/19.3.0.0.0/instantclient-basiclite-linux.x64-19.3.0.0.0dbru.zip -O
RUN cd /tmp && curl -L https://raw.github.com/adrianharabula/php7-with-oci8/master/instantclient/19.3.0.0.0/instantclient-sdk-linux.x64-19.3.0.0.0dbru.zip -O
RUN cd /tmp && curl -L https://raw.github.com/adrianharabula/php7-with-oci8/master/instantclient/19.3.0.0.0/instantclient-sqlplus-linux.x64-19.3.0.0.0dbru.zip -O

RUN unzip /tmp/instantclient-basiclite-linux.x64-19.3.0.0.0dbru.zip -d /usr/local/
RUN unzip /tmp/instantclient-sdk-linux.x64-19.3.0.0.0dbru.zip -d /usr/local/
RUN unzip /tmp/instantclient-sqlplus-linux.x64-19.3.0.0.0dbru.zip -d /usr/local/

RUN ln -s /usr/local/instantclient_19_3 /usr/local/instantclient
# fixes error "libnnz19.so: cannot open shared object file: No such file or directory"
RUN ln -s /usr/local/instantclient/lib* /usr/lib
RUN ln -s /usr/local/instantclient/sqlplus /usr/bin/sqlplus

RUN echo 'export LD_LIBRARY_PATH="/usr/local/instantclient"' >> /root/.bashrc
RUN echo 'umask 002' >> /root/.bashrc

RUN echo 'instantclient,/usr/local/instantclient' | pecl install oci8-2.2.0
RUN echo "extension=oci8.so" > /usr/local/etc/php/conf.d/php-oci8.ini
RUN cd /build/phpADSV7/lib/php/ZendFramework/2.2.5/zf2/ && composer install
COPY source/vendor/ /build/phpADSV7/lib/php/ZendFramework/2.2.5/zf2/vendor/
COPY source/ParameterContainer.php /build/phpADSV7/lib/php/ZendFramework/2.2.5/zf2/vendor/zendframework/zend-db/src/Adapter/
RUN cd /build/phpADSV7/lib/php/ZendFramework/2.2.5/zf2/ && composer dump-autoload