#!/bin/sh

# Fix Docker socket permissions
if [ -S /var/run/docker.sock ]; then
    echo "Fixing Docker socket permissions..."
    # Get the GID of the docker.sock on host
    DOCKER_SOCK_GID=$(stat -c '%g' /var/run/docker.sock)

    echo "Docker socket GID: $DOCKER_SOCK_GID"

    # Remove existing docker group if it exists with wrong GID
    if getent group docker > /dev/null 2>&1; then
        CURRENT_DOCKER_GID=$(getent group docker | cut -d: -f3)
        if [ "$CURRENT_DOCKER_GID" != "$DOCKER_SOCK_GID" ]; then
            echo "Removing docker group with wrong GID ($CURRENT_DOCKER_GID)"
            delgroup docker 2>/dev/null || true
        fi
    fi

    # Create docker group with correct GID
    if ! getent group docker > /dev/null 2>&1; then
        echo "Creating docker group with GID $DOCKER_SOCK_GID"
        addgroup -g "$DOCKER_SOCK_GID" docker
    fi

    # Add www-data to docker group
    if ! id -nG www-data | grep -qw docker; then
        echo "Adding www-data to docker group"
        addgroup www-data docker
    fi

    # Change socket ownership to root:docker
    echo "Changing docker socket ownership..."
    chown root:docker /var/run/docker.sock 2>/dev/null || echo "Could not change socket ownership (may need root)"

    # Make socket writable by group
    chmod 660 /var/run/docker.sock 2>/dev/null || echo "Could not change socket permissions (may need root)"

    echo "Docker socket permissions fixed!"
    echo "www-data groups: $(id -nG www-data)"
fi

# Start supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
