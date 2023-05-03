#!/bin/bash

## The public URL on which the application will be available
APP_URL=${APP_URL:-}
## Application key
APP_KEY=${APP_KEY:-}
## Enable/Disable the debug mode
APP_DEBUG=${APP_DEBUG:-false}

## Queue connection for job processing
QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}

## Cache driver
CACHE_DRIVER=${CACHE_DRIVER:-file}

## Session Driver
SESSION_DRIVER=${SESSION_DRIVER:-database}

## Redis host
REDIS_HOST=${REDIS_HOST:-redis}

## Mailer
MAIL_MAILER=${MAIL_MAILER:-log}

## User under which the commands will run
SETUP_USER=www-data
## Directory where the code is located
WORKDIR=/var/www

## Startup service configuration files to pass to Supervisor
STARTUP_SERVICES=${STARTUP_SERVICES:-"web,cron,runners"}

function startup_config () {
    echo "Configuring..."
    echo "- Writing php configuration..."
    
    # Set post and upload size for php if customized for the specific deploy
    cat > /usr/local/etc/php/conf.d/php-runtime.ini <<-EOM &&
		post_max_size=${PHP_POST_MAX_SIZE}
        upload_max_filesize=${PHP_UPLOAD_MAX_FILESIZE}
        memory_limit=${PHP_MEMORY_LIMIT}
        max_input_time=${PHP_MAX_INPUT_TIME}
        max_execution_time=${PHP_MAX_EXECUTION_TIME}
	EOM

    write_config &&
    init_empty_dir $WORKDIR/storage && 
    wait_services &&
    install_or_update &&
    ensure_permissions_on_folders &&
    activate_supervisor_configuration_files &&
    run_post_upgrade_processes &&
	echo "Configuration completed."

}

function write_config() {

    if [ -z "$APP_URL" ]; then
        # application URL not set
        echo "**************"
        echo "Public URL not set. Set the public URL using APP_URL."
        echo "**************"
        return 240
    fi

    if [ -z "$APP_KEY" ]; then
        # application Key not set
        echo "**************"
        echo "Application key not set. Set the application key using APP_KEY. You can generate one using php artisan key:generate --show (for more information https://tighten.co/blog/app-key-and-you)"
        echo "**************"
        return 240
    fi

    echo "- Writing env file..."

	cat > ${WORKDIR}/.env <<-EOM &&
		APP_KEY=${APP_KEY}
		APP_URL=${APP_URL}
		APP_ENV=production
		APP_DEBUG=${APP_DEBUG}
		DB_DATABASE=${DB_DATABASE}
		DB_HOST=${DB_HOST}
		DB_USERNAME=${DB_USERNAME}
		DB_PASSWORD=${DB_PASSWORD}
        CACHE_DRIVER=${CACHE_DRIVER}
        QUEUE_CONNECTION=${QUEUE_CONNECTION}
        SESSION_DRIVER=${SESSION_DRIVER}
        REDIS_HOST=${REDIS_HOST}
        MAIL_MAILER=${MAIL_MAILER}
        MAIL_HOST=${MAIL_HOST}
        MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}
        MAIL_FROM_NAME="${MAIL_FROM_NAME}"
        MAIL_USERNAME=${MAIL_USERNAME}
        MAIL_PASSWORD=${MAIL_PASSWORD}
	EOM

    php artisan config:clear -q
    php artisan route:clear -q

	echo "- ENV file written! $WORKDIR/.env"
}

function install_or_update() {
    cd ${WORKDIR} || return 242

    php artisan config:clear
    php artisan migrate --force

    php artisan config:cache
    php artisan route:cache
    php artisan optimize
}

function run_post_upgrade_processes() {
    cd ${WORKDIR} || return 242

    php artisan operations:process
}

function wait_services () {
    ## Wait for the database service to accept connections
    php -f /usr/local/bin/db-connect-test.php -- -d "${DB_DATABASE}" -H "${DB_HOST}" -u "${DB_USERNAME}" -p "${DB_PASSWORD}"
}

## Initialize an empty storage directory with the required default folders
function init_empty_dir() {
    local dir_to_init=$1

    echo "- ${dir_to_init}"
    echo "- Checking storage directory structure..."

    if [ ! -d "${dir_to_init}/framework/cache" ]; then
        mkdir -p "${dir_to_init}/framework/cache"
        chgrp -R $SETUP_USER "${dir_to_init}/framework/cache"
        chmod -R g+rw "${dir_to_init}/framework/cache"
        echo "-- [framework/cache] created."
    fi
    if [ ! -d "${dir_to_init}/framework/cache/data" ]; then
        mkdir -p "${dir_to_init}/framework/cache/data"
        chgrp -R $SETUP_USER "${dir_to_init}/framework/cache/data"
        chmod -R g+rw "${dir_to_init}/framework/cache/data"
        echo "-- [framework/cache/data] created."
    fi
    if [ ! -d "${dir_to_init}/framework/sessions" ]; then
        mkdir -p "${dir_to_init}/framework/sessions"
        chgrp -R $SETUP_USER "${dir_to_init}/framework/sessions"
        chmod -R g+rw "${dir_to_init}/framework/sessions"
        echo "-- [framework/sessions] created."
    fi
    if [ ! -d "${dir_to_init}/framework/views" ]; then
        mkdir -p "${dir_to_init}/framework/views"
        chgrp -R $SETUP_USER "${dir_to_init}/framework/views"
        chmod -R g+rw "${dir_to_init}/framework/views"
        echo "-- [framework/views] created."
    fi
    if [ ! -d "${dir_to_init}/logs" ]; then
        mkdir -p "${dir_to_init}/logs"
        chgrp -R $SETUP_USER "${dir_to_init}/logs"
        chmod -R g+rw "${dir_to_init}/logs"
        echo "-- [logs] created."
    fi
    if [ ! -d "${dir_to_init}/app" ]; then
        mkdir -p "${dir_to_init}/app"
        chgrp -R $SETUP_USER "${dir_to_init}/app"
        chmod -R g+rw "${dir_to_init}/app"
        echo "-- [app] created."
    fi
    if [ ! -d "${dir_to_init}/app/public" ]; then
        mkdir -p "${dir_to_init}/app/public"
        chgrp -R $SETUP_USER "${dir_to_init}/app/public"
        chmod -R g+rw "${dir_to_init}/app/public"
        echo "-- [app/public] created."
    fi

    php artisan storage:link
}

function ensure_permissions_on_folders() {
    echo "- Ensure bootstrap/cache is writable"
    chgrp -R $SETUP_USER $WORKDIR/bootstrap/cache
    chmod -R g+rw $WORKDIR/bootstrap/cache
    
    echo "- Ensure storage is writable"
    chgrp -R $SETUP_USER $WORKDIR/storage
    chmod -R g+rw $WORKDIR/storage
}

function activate_supervisor_configuration_files() {
    
    if [[ "$STARTUP_SERVICES" == *"web"* ]]; then
        cp /etc/supervisor/conf.d/stubs/web.conf /etc/supervisor/conf.d/web.active.conf
        echo "- Web services enabled"
    fi
    
    if [[ "$STARTUP_SERVICES" == *"cron"* ]]; then
        cp /etc/supervisor/conf.d/stubs/cron.conf /etc/supervisor/conf.d/cron.active.conf
        echo "- Cron service enabled"
    fi
    
    if [[ "$STARTUP_SERVICES" == *"runners"* ]]; then
        cp /etc/supervisor/conf.d/stubs/runners.conf /etc/supervisor/conf.d/runners.active.conf
        echo "- Runner services enabled"
    fi

}

startup_config >&2
