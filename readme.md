**Automatic Configuration**

1. Put the entire heirarchy into a php-enabled server and load up the index.php file, this will load a configuration page the first time it is run.
2. Fill out all fields, noting especially the `masterUsername` and `masterPassword` which will serve as your master credentials
3. If you choose 'file' for the credential server type, the server will load `php/settings/credentials.json`
4. Test your configuration, this will indicate SQL errors, permission errors, and configuration problems
5. Accept your configuration and login using either your master credentials or your chosen credential container

**Manual Editing `credentials.json`**

config skeleton:

	{
		"username": {"password":"password", "securityLevel":10}
	}


**Manual Configuration**

edit the php/settings/settings.json using the following as a guide

configuration skeleton:

	{
		"dbtype": "",
		"server": "",
		"database": "",
		"table": "",
		"user": "",
		"pass": "",
		"credentialServerType": "",
		"credentialServer": "",
		"credentialDatabase": "",
		"credentialUsername": "",
		"credentialPassword": "",
		"credentialTable": "",
		"credentialUserColumn": "",
		"credentialPassColumn": "",
		"credentialAdminColumn": "",
		"indexedOnly":true,
	}
	
* `dbtype` is the database type, it can be either mysql, pgsql, or mssql
* `server` is the address of the db server
* `database` is the root database for this configuration
* `table` is the root table for this configuration
* `user` & `pass` are the db username and password for accessing the db

the credential subset is where you have the users who will use this page stored, this can be a completely separate db from your normal db

* `credentialUserColumn` is the username column to match up with the login username
* `credentialPassColumn` is the password column to match up with the login password
* `credentialAdminColumn` is the column that stores the account security level of that user, which is currently the method that determines admin access (>=100)

* `indexedOnly` indicates the ability to sort/search be restricted to those columns that are indexed

**Dockerfile Configuration**

    
    # Pull from the ubuntu:14.04 image
    FROM ubuntu:14.04
    
    # Set the author
    MAINTAINER Stephen <stephen@dispatch.me>
    
    # Update cache and install base packages
    RUN apt-get update && apt-get -y install \
        software-properties-common \
        python-software-properties \
        debian-archive-keyring \
        wget \
        curl \
        vim \
        aptitude \
        dialog \
        net-tools \
        mcrypt \
        build-essential \
        tcl8.5 \
        git \
        python
    
    # Download Nginx signing key
    RUN apt-key adv --recv-keys --keyserver keyserver.ubuntu.com C300EE8C
    
    # Add to repository sources list
    RUN add-apt-repository ppa:nginx/stable
    
    # Update cache and install Nginx
    RUN apt-get update && apt-get -y install \
        nginx \
        php5-fpm \
        php5-cli \
        php5-mysql \
        php5-curl \
        php5-mcrypt \
        php5-gd \
        php5-redis \
        php5-pgsql
    
    # Turn off daemon mode
    # Reference: http://stackoverflow.com/questions/18861300/how-to-run-nginx-within-docker-container-without-halting
    RUN echo "\ndaemon off;" >> /etc/nginx/nginx.conf
    
    # Backup the default configurations
    RUN cp /etc/php5/fpm/php.ini /etc/php5/fpm/php.ini.original.bak
    RUN mv /etc/nginx/sites-available/default /etc/nginx/sites-available/default.original
    
    # Configure PHP settings
    RUN perl -pi -e 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/g' /etc/php5/fpm/php.ini
    RUN perl -pi -e 's/allow_url_fopen = Off/allow_url_fopen = On/g' /etc/php5/fpm/php.ini
    RUN perl -pi -e 's/expose_php = On/expose_php = Off/g' /etc/php5/fpm/php.ini
    RUN perl -pi -e 's/display_errors = Off/display_errors = On/g' /etc/php5/fpm/php.ini
    
    # Copy default site conf
    COPY default.conf /etc/nginx/sites-available/default
    
    # Copy configurator script which allows the user to pass ENV variables to nginx
    COPY configurator.py /var/www/html/
    COPY Dockerfile /tmp/Dockerfile
    
    # Set the current working directory
    WORKDIR /var/www/html
    
    # Execute the python configurator, which copies all environment variables and values to the nginx configuration file (after a line that includes: include fastcgi_params;)
    )
    RUN python configurator.py /tmp/Dockerfile /etc/nginx/sites-available/default
    
    # Mount volumes
    VOLUME ["/etc/nginx/certs", "/etc/nginx/conf.d", "/var/www/html"]
    
    # Boot up Nginx, and PHP5-FPM when container is started
    CMD service php5-fpm start && nginx
    
    # Expose port 8080
    EXPOSE 8080
    
    
1. CONFIGURE: make a new Dockerfile in the root directory, include the above text
    * ENV DBVIEW_INDEXED_ONLY 
    * ENV DBVIEW_ROOT_OF_PAGE 
    * ENV DBVIEW_MASTER_USERNAME 
    * ENV DBVIEW_MASTER_PASSWORD 
    * ENV DBVIEW_DBTYPE 
    * ENV DBVIEW_SERVER 
    * ENV DBVIEW_DATABASE 
    * ENV DBVIEW_USER 
    * ENV DBVIEW_PASS 
    * ENV DBVIEW_CREDENTIAL_SERVER_TYPE 
    * ENV DBVIEW_CREDENTIAL_SERVER 
    * ENV DBVIEW_CREDENTIAL_DATABASE 
    * ENV DBVIEW_CREDENTIAL_USERNAME 
    * ENV DBVIEW_CREDENTIAL_PASSWORD 
    * ENV DBVIEW_CREDENTIAL_TABLE 
    * ENV DBVIEW_CREDENTIAL_USER_COLUMN 
    * ENV DBVIEW_CREDENTIAL_PASS_COLUMN 
    * ENV DBVIEW_CREDENTIAL_ADMIN_COLUMN 
    * ENV DBVIEW_USER_RO 
    * ENV DBVIEW_PASS_RO 
    * ENV DBVIEW_MAIN_TABLE 
    * ENV DBVIEW_TABLE_LIST 
2. BUILD: docker build -t {TAG} .
3. RUN: docker run --name {NAME} -p {LOCAL_PORT}:8080 -v {LOCAL_PATH}:/var/www/html -itP {TAG}
