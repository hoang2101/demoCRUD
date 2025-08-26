# Sử dụng PHP 8.2 image làm base
FROM php:8.3-fpm

# Cài đặt các dependencies cần thiết
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Cài đặt Composer từ image của Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy toàn bộ mã nguồn vào container
COPY . /app

WORKDIR /app

# Cài đặt các gói PHP từ Composer
RUN composer install --no-dev --optimize-autoloader

# Expose cổng 8000 để truy cập ứng dụng
EXPOSE 8000

# Chạy PHP server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
