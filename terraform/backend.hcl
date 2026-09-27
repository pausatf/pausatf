# Shared S3 backend configuration for DigitalOcean Spaces.
# Used by all environments via: terraform init -backend-config=../../backend.hcl
# DigitalOcean requires us-east-1 as the S3 signing region, regardless of the
# Spaces endpoint region. Callers must also set AWS_REGION and
# AWS_DEFAULT_REGION to this value because AWS SDK environment settings can
# override the backend config.
endpoints = {
  s3 = "https://sfo2.digitaloceanspaces.com"
}
region                       = "us-east-1"
bucket                       = "pausatf-terraform-state"
skip_credentials_validation  = true
skip_metadata_api_check      = true
skip_region_validation       = true
skip_requesting_account_id   = true
skip_s3_checksum             = true
use_lockfile                 = true
