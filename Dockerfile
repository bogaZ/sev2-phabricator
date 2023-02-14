FROM php:7.4-fpm-alpine3.12
##
# PHP 7.X

RUN addgroup -S appgroup && adduser -S appuser -G appgroup
RUN addgroup -S git && adduser -S git -G git

# RUN echo "http://dl-4.alpinelinux.org/alpine/edge/testing" >> /etc/apk/repositories

#Install PHP7, Pygments and Git
RUN apk --no-cache add \
        g++ \
        make \
        sudo \
        ncurses \
        nginx \
        php7 \
        php7-dom \
        php7-ctype \
        php7-curl \
        php7-fpm \
        php7-gd \
        php7-intl \
        php7-json \
        php7-mbstring \
        php7-mcrypt \
        php7-mysqlnd \
        php7-opcache \
        php7-openssl \
        php7-pcntl \
        php7-mysqli \
        php7-pdo \
        php7-pdo_mysql \
        php7-posix \
        php7-session \
        php7-tidy \
        php7-xml \
        php7-zip \
        php7-pecl-apcu \
        php7-gd \
        php7-fileinfo \
        php7-phar \
        php7-tokenizer \
        php7-xmlwriter \
        composer \
        procps \
        supervisor \
        git \
        py-pygments \
        curl \
        openssh \
        nodejs \
        npm \
        tzdata \
        gettext \
        zlib-dev \
        php7-pear  \
        php7-dev  \
        gcc  \
        musl-dev \
        linux-headers \
        libzip-dev \
        icu-dev

RUN apk add grpc

# Set timezone
ENV TZ Asia/Jakarta

# Copy configs for SSHD PHP7 NGINX and Phabricator
RUN ssh-keygen -A
COPY manifest/phab/phab-sudo       /etc/sudoers.d/phab-sudo
COPY manifest/phab/ssh-hook.sh     /usr/libexec/ssh-hook.sh
COPY manifest/phab/sshd_config     /etc/ssh/sshd_config.phabricator
RUN chown -R nginx:www-data /var/lib/nginx
COPY manifest/nginx/nginx.conf     /etc/nginx/nginx.conf
COPY manifest/php7/php.ini         /etc/php7/php.ini
COPY manifest/php7/php-fpm.conf    /etc/php7/php-fpm.conf
COPY manifest/php7/local.json.dist /local.json.dist
COPY manifest/php7/supervisord.conf /etc/supervisord.conf

# Set entrypoint
COPY manifest/entrypoint.sh   /

# Link php7 exec to more standard php name
# Remove useless config
# Phabricator code, File storage, Code storage and missing PATH folder
# Redirect the daemon logs
RUN rm -rf /etc/php7/php-fpm.d \
  && mkdir -p /srv \
  && mkdir -p /data \
  && mkdir -p /repo \
  && mkdir -p /usr/local/sbin \
  && chmod +x /entrypoint.sh

RUN docker-php-ext-install zip
RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install pcntl
RUN pecl install grpc && docker-php-ext-enable grpc

ADD . /srv/phabricator

# Install all deps
RUN git clone https://github.com/phacility/libphutil.git /srv/libphutil \
    && git clone https://github.com/phacility/arcanist.git /srv/arcanist
RUN cd /srv/phabricator/externals/firebase-php && composer install
RUN cd /srv/phabricator/support/aphlict/server && npm install ws

# Set port
EXPOSE 80
EXPOSE 2222
EXPOSE 22280

ENTRYPOINT ["/entrypoint.sh"]
