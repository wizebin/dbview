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
        git
    
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
        php5-redis
    
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
    
    # Copy the index.php file
    #ADD . /var/www/html/
    
    # Mount volumes
    VOLUME ["/etc/nginx/certs", "/etc/nginx/conf.d", "/var/www/html"]
    
    # Boot up Nginx, and PHP5-FPM when container is started
    CMD service php5-fpm start && nginx
    
    # Set the current working directory
    WORKDIR /var/www/html
    
    # Expose port 8080
    EXPOSE 8080

1. CONFIGURE: make a new Dockerfile in the root directory, include the above text
    * ENV INDEXED_ONLY 
    * ENV ROOT_OF_PAGE 
    * ENV MASTER_USERNAME 
    * ENV MASTER_PASSWORD 
    * ENV DBTYPE 
    * ENV SERVER 
    * ENV DATABASE 
    * ENV USER 
    * ENV PASS 
    * ENV CREDENTIAL_SERVER_TYPE 
    * ENV CREDENTIAL_SERVER 
    * ENV CREDENTIAL_DATABASE 
    * ENV CREDENTIAL_USERNAME 
    * ENV CREDENTIAL_PASSWORD 
    * ENV CREDENTIAL_TABLE 
    * ENV CREDENTIAL_USER_COLUMN 
    * ENV CREDENTIAL_PASS_COLUMN 
    * ENV CREDENTIAL_ADMIN_COLUMN 
    * ENV USER_RO 
    * ENV PASS_RO 
    * ENV MAIN_TABLE 
    * ENV TABLE_LIST 
2. BUILD: docker build -t {TAG}
3. RUN: docker run --name {NAME} -p {LOCAL_PORT}:8080 -v {LOCAL_PATH}:/var/www/html -itP {TAG}
