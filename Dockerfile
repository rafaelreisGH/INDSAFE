# Comece a partir da imagem oficial que já estamos usando.
FROM php:apache

# Use o script oficial da imagem PHP para instalar as extensões necessárias.
# Aqui, estamos instalando a extensão PDO e o driver específico do MySQL para o PDO.
RUN docker-php-ext-install pdo pdo_mysql

# ATENÇÃO: Adicione a seguinte linha para habilitar o mod_rewrite
RUN a2enmod rewrite

# ATENÇÃO: Copia o arquivo de configuração para o contêiner.
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf