#!/bin/bash

echo "Service start in progress..." && \
/usr/local/bin/configure.sh && \
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/services.conf