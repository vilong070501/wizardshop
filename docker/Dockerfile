FROM php:8.2-apache

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo pdo_mysql intl zip

RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
   mv composer.phar /usr/local/bin/composer

RUN apt update && apt install -yqq \
    nodejs \
    npm \
    vim

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt install symfony-cli -y

COPY . /var/www/

COPY ./docker/apache.conf /etc/apache2/sites-available/000-default.conf

RUN cd /var/www && \
    composer install && \
    npm install --force && \
    npm run build

RUN a2enmod rewrite

WORKDIR /var/www/

EXPOSE 80