# Shared S3 backend configuration for DigitalOcean Spaces
# Used by all environments via: terraform init -backend-config=../../backend.hcl
endpoint                    = "sfo2.digitaloceanspaces.com"
region                      = "us-west-1"
bucket                      = "pausatf-terraform-state"
skip_credentials_validation = true
skip_metadata_api_check     = true
skip_region_validation      = true
use_lockfile                = true
