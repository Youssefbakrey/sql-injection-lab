FROM php:8.2-apache

# تثبيت ملحقات PHP
RUN docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli

# نسخ ملفات المشروع (فقط index.php)
COPY index.php /var/www/html/index.php

# ضبط الصلاحيات
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# تثبيت MySQL client
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# سكريبت البدء
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
