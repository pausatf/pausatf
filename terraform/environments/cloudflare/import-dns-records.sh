#!/usr/bin/env bash
# Import all Cloudflare DNS records into Terraform state
# Usage: ./import-dns-records.sh

set -euo pipefail

ZONE_ID="67b87131144a68ad5ed43ebfd4e6d811"
API_TOKEN="${TF_VAR_cloudflare_api_token:-}"

if [ -z "$API_TOKEN" ]; then
  echo "Error: TF_VAR_cloudflare_api_token not set"
  echo "Run: export TF_VAR_cloudflare_api_token='your-token'"
  exit 1
fi

echo "Fetching DNS records from Cloudflare..."

# Get all DNS records with IDs
RECORDS=$(curl -s "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records" \
  -H "Authorization: Bearer ${API_TOKEN}" | \
  jq -r '.result[] | "\(.type)|\(.name)|\(.id)"')

# Map DNS record names to Terraform resource names
declare -A RESOURCE_MAP=(
  # A Records
  ["A|pausatf.org"]="root"
  ["A|www.pausatf.org"]="www"
  ["A|ftp.pausatf.org"]="ftp"
  ["A|mail.pausatf.org"]="mail"
  ["A|monitor.pausatf.org"]="monitor"
  ["A|stage.pausatf.org"]="stage"
  ["A|staging.pausatf.org"]="staging"

  # CNAME Records
  ["CNAME|prod.pausatf.org"]="prod"
  ["CNAME|51871933.pausatf.org"]="sendgrid_51871933"
  ["CNAME|em5172.pausatf.org"]="sendgrid_em5172"
  ["CNAME|url7068.pausatf.org"]="sendgrid_url7068"
  ["CNAME|url7741.pausatf.org"]="sendgrid_url7741"
  ["CNAME|s1._domainkey.pausatf.org"]="sendgrid_dkim_s1"
  ["CNAME|s2._domainkey.pausatf.org"]="sendgrid_dkim_s2"

  # TXT Records
  ["TXT|pausatf.org|spf"]="spf"
  ["TXT|pausatf.org|google"]="google_site_verification"
  ["TXT|_dmarc.pausatf.org"]="dmarc"
  ["TXT|cf2024-1._domainkey.pausatf.org"]="dkim_cloudflare"
  ["TXT|_acme-challenge.www.pausatf.org"]="acme_challenge_www"
)

echo ""
echo "Importing DNS records..."

# Import zone first
echo "Importing zone..."
terraform import cloudflare_zone.pausatf "${ZONE_ID}" 2>/dev/null && \
  echo "✓ Zone imported" || echo "⚠ Zone already imported or failed"

# Counter for records
IMPORTED=0
SKIPPED=0
FAILED=0

# Import each DNS record
while IFS='|' read -r type name id; do
  # Handle multiple TXT records with same name
  if [ "$type" = "TXT" ]; then
    # Get TXT content to determine which resource
    CONTENT=$(echo "$RECORDS" | grep "^TXT|${name}|" | head -1)
    if [[ "$CONTENT" =~ "spf" ]]; then
      RESOURCE_NAME="spf"
    elif [[ "$CONTENT" =~ "google-site-verification" ]]; then
      RESOURCE_NAME="google_site_verification"
    elif [[ "$CONTENT" =~ "DMARC" ]]; then
      RESOURCE_NAME="dmarc"
    elif [[ "$CONTENT" =~ "DKIM" ]]; then
      RESOURCE_NAME="dkim_cloudflare"
    elif [[ "$CONTENT" =~ "acme-challenge" ]]; then
      RESOURCE_NAME="acme_challenge_www"
    else
      continue
    fi
  else
    # For non-TXT records, use the map
    KEY="${type}|${name}"
    RESOURCE_NAME="${RESOURCE_MAP[$KEY]:-}"
  fi

  if [ -z "$RESOURCE_NAME" ]; then
    echo "⚠ Skipping unmapped record: ${type} ${name}"
    ((SKIPPED++))
    continue
  fi

  echo -n "Importing ${type} ${name} as cloudflare_record.${RESOURCE_NAME}... "

  if terraform import "cloudflare_record.${RESOURCE_NAME}" "${id}" 2>/dev/null; then
    echo "✓"
    ((IMPORTED++))
  else
    if terraform state show "cloudflare_record.${RESOURCE_NAME}" >/dev/null 2>&1; then
      echo "⚠ Already imported"
      ((SKIPPED++))
    else
      echo "✗ Failed"
      ((FAILED++))
    fi
  fi
done <<< "$RECORDS"

# Import MX records (special handling for multiple records with same name)
echo ""
echo "Importing MX records..."

MX_RECORDS=$(curl -s "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records?type=MX" \
  -H "Authorization: Bearer ${API_TOKEN}" | \
  jq -r '.result[] | "\(.priority)|\(.content)|\(.id)"')

declare -A MX_MAP=(
  ["1|aspmx.l.google.com"]="mx_primary"
  ["5|alt1.aspmx.l.google.com"]="mx_alt1"
  ["5|alt2.aspmx.l.google.com"]="mx_alt2"
  ["10|alt3.aspmx.l.google.com"]="mx_alt3"
  ["10|alt4.aspmx.l.google.com"]="mx_alt4"
)

while IFS='|' read -r priority content id; do
  KEY="${priority}|${content}"
  RESOURCE_NAME="${MX_MAP[$KEY]:-}"

  if [ -z "$RESOURCE_NAME" ]; then
    echo "⚠ Skipping unmapped MX record: ${priority} ${content}"
    ((SKIPPED++))
    continue
  fi

  echo -n "Importing MX ${content} as cloudflare_record.${RESOURCE_NAME}... "

  if terraform import "cloudflare_record.${RESOURCE_NAME}" "${id}" 2>/dev/null; then
    echo "✓"
    ((IMPORTED++))
  else
    if terraform state show "cloudflare_record.${RESOURCE_NAME}" >/dev/null 2>&1; then
      echo "⚠ Already imported"
      ((SKIPPED++))
    else
      echo "✗ Failed"
      ((FAILED++))
    fi
  fi
done <<< "$MX_RECORDS"

# Import CAA records
echo ""
echo "Importing CAA records..."

CAA_RECORDS=$(curl -s "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records?type=CAA" \
  -H "Authorization: Bearer ${API_TOKEN}" | \
  jq -r '.result[] | "\(.data.tag)|\(.data.value)|\(.id)"')

declare -A CAA_MAP=(
  ["issue|letsencrypt.org"]="caa_letsencrypt_issue"
  ["issuewild|letsencrypt.org"]="caa_letsencrypt_issuewild"
  ["issue|digicert.com"]="caa_digicert_issue"
  ["issuewild|digicert.com"]="caa_digicert_issuewild"
  ["iodef|mailto:admin@pausatf.org"]="caa_iodef"
)

while IFS='|' read -r tag value id; do
  KEY="${tag}|${value}"
  RESOURCE_NAME="${CAA_MAP[$KEY]:-}"

  if [ -z "$RESOURCE_NAME" ]; then
    echo "⚠ Skipping unmapped CAA record: ${tag} ${value}"
    ((SKIPPED++))
    continue
  fi

  echo -n "Importing CAA ${tag} ${value} as cloudflare_record.${RESOURCE_NAME}... "

  if terraform import "cloudflare_record.${RESOURCE_NAME}" "${id}" 2>/dev/null; then
    echo "✓"
    ((IMPORTED++))
  else
    if terraform state show "cloudflare_record.${RESOURCE_NAME}" >/dev/null 2>&1; then
      echo "⚠ Already imported"
      ((SKIPPED++))
    else
      echo "✗ Failed"
      ((FAILED++))
    fi
  fi
done <<< "$CAA_RECORDS"

echo ""
echo "=========================================="
echo "Import Summary:"
echo "  Imported: ${IMPORTED}"
echo "  Skipped:  ${SKIPPED}"
echo "  Failed:   ${FAILED}"
echo "=========================================="

if [ "$FAILED" -gt 0 ]; then
  echo "⚠ Some imports failed. Check errors above."
  exit 1
fi

echo ""
echo "✓ All DNS records imported successfully!"
echo ""
echo "Next steps:"
echo "  1. Run 'terraform plan' to verify no changes"
echo "  2. If plan shows changes, review and adjust Terraform config"
echo "  3. Commit state changes to version control"
