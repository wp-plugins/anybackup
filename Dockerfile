FROM tutum/apache-php:latest
MAINTAINER mikkel@255bits.com, martyn@255bits.com

# Install packages
RUN apt-get update
RUN DEBIAN_FRONTEND=noninteractive apt-get -y install mysql-client

# Download latest version of Wordpress into /app
RUN rm -fr /app && git clone --depth=1 https://github.com/WordPress/WordPress.git /app

# Add wp-config with info for Wordpress to connect to DB
ADD wp-config.php /app/wp-config.php
RUN chmod 644 /app/wp-config.php
# Modify permissions to allow plugin upload
RUN chmod -R 777 /app/wp-content

# Add script to create 'wordpress' DB
ADD run.sh /run.sh
ADD install-wp-cli.sh /install-wp-cli.sh
RUN chmod 755 /*.sh

# Expose environment variables
ENV DB_HOST **LinkMe**
ENV DB_PORT **LinkMe**
ENV DB_NAME wordpress
ENV DB_USER admin
ENV DB_PASS **ChangeMe**

EXPOSE 80
RUN ["/install-wp-cli.sh"]
ADD build/anybackup.zip /anybackup.zip
CMD ["/run.sh"]
