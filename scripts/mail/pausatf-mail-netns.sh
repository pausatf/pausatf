#!/usr/bin/env bash
# pausatf-mail runs with network_mode "service:wordpress": it lives in the
# WordPress container's network namespace. Docker records that as the container
# ID, so recreating WordPress on its own leaves mail attached to a namespace that
# no longer exists. WordPress then gets "connection refused" on 127.0.0.1:25 and
# mail cannot resolve its relay. Recreate mail whenever the IDs differ.
set -euo pipefail
readonly COMPOSE_DIR=${PAUSATF_COMPOSE_DIR:-/home/somethingwithproof/pausatf-deployment}
readonly COMPOSE_FILE=${PAUSATF_COMPOSE_FILE:-docker-compose.prod.yml}
readonly WORDPRESS=${PAUSATF_WORDPRESS_CONTAINER:-pausatf-wordpress}
readonly MAIL=${PAUSATF_MAIL_CONTAINER:-pausatf-mail}

attached_to() {
  docker inspect --format '{{.HostConfig.NetworkMode}}' "$MAIL"
}

# Mid-deploy WordPress may be absent or stopped; the next run will see the result.
if [[ $(docker inspect --format '{{.State.Running}}' "$WORDPRESS" 2>/dev/null) != true ]]; then
  exit 0
fi
wordpress_id=$(docker inspect --format '{{.Id}}' "$WORDPRESS")
if [[ $(attached_to) == "container:$wordpress_id" ]]; then
  exit 0
fi

echo "$MAIL is attached to $(attached_to), but $WORDPRESS is $wordpress_id; recreating $MAIL"
cd "$COMPOSE_DIR"
docker compose -f "$COMPOSE_FILE" up -d --no-deps --no-build mail
[[ $(attached_to) == "container:$wordpress_id" ]]
echo "$MAIL now shares the network namespace of $WORDPRESS"
