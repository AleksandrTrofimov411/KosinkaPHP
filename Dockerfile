FROM ubuntu:latest
FROM php:8
COPY . /KosinkaPHP
WORKDIR /KosinkaPHP
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-scripts
LABEL authors="Александр"
ENTRYPOINT ["php", "main.php"]