#!/bin/sh

# Fix Docker socket permissions
if [ -S /var/run/docker.sock ]; then
    echo "Fixing Docker socket permissions..."
    # Get the GID of the docker.sock
    DOCKER_SOCK_GID=$(stat -c '%g' /var/run/docker.sock)

    # Check if docker group exists
    if ! getent group docker > /dev/null 2>&1; then
        echo "Creating docker group with GID $DOCKER_SOCK_GID"
        addgroup -g "$DOCKER_SOCK_GID" docker
    fi

    # Add www-data to docker group
    if ! id -nG www-data | grep -qw docker; then
        echo "Adding www-data to docker group"
        addgroup www-data docker
    fi

    echo "Docker socket permissions fixed!"
fi

# Start supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
