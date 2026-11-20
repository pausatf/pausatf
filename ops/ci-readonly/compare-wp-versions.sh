#!/usr/bin/env bash
# Reads the wp-versions.php JSON on stdin and prints update metadata JSON:
# {"core_version", "core_updates": [...], "plugin_updates": [...]}.
# Plugins that wordpress.org does not know (premium or custom) are skipped.
set -euo pipefail

inventory="$(cat)"
api=https://api.wordpress.org
newer() { [[ "$1" != "$2" && "$(printf '%s\n%s\n' "$1" "$2" | sort -V | tail -n1)" == "$2" ]]; }

core_version="$(jq -r '.core_version' <<< "$inventory")"
[[ "$core_version" =~ ^[0-9]+\.[0-9] ]] || { echo "Inventory has no core version" >&2; exit 1; }
latest_core="$(curl -fsS --proto '=https' --retry 3 --max-time 30 "$api/core/version-check/1.7/" | jq -r '.offers[0].current')"
[[ -n "$latest_core" && "$latest_core" != null ]] || { echo "No core version from wordpress.org" >&2; exit 1; }
core_updates='[]'
if newer "$core_version" "$latest_core"; then
  core_updates="$(jq -nc --arg v "$latest_core" '[{version:$v, update_type:"upgrade"}]')"
fi

plugin_updates='[]'
while IFS=$'\t' read -r slug name version status; do
  info="$(curl -sS --proto '=https' --retry 3 --max-time 30 -G "$api/plugins/info/1.2/" \
    --data-urlencode action=plugin_information \
    --data-urlencode "request[slug]=$slug" \
    --data-urlencode 'request[fields][sections]=0')" || { echo "wordpress.org lookup failed for $slug" >&2; exit 1; }
  latest="$(jq -r '.version // empty' <<< "$info" 2>/dev/null || true)"
  [[ -z "$latest" ]] && continue
  if newer "$version" "$latest"; then
    plugin_updates="$(jq -c --arg n "$name" --arg v "$version" --arg u "$latest" --arg s "$status" \
      '. + [{name:$n, version:$v, update_version:$u, status:$s}]' <<< "$plugin_updates")"
  fi
done < <(jq -r '.plugins[] | [.slug, .name, .version, .status] | @tsv' <<< "$inventory")

jq -nc --arg c "$core_version" --argjson cu "$core_updates" --argjson pu "$plugin_updates" \
  '{core_version:$c, core_updates:$cu, plugin_updates:$pu}'
