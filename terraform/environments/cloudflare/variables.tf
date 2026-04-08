variable "cloudflare_api_token" {
  description = "Cloudflare API token with Zone.DNS and Zone.Zone permissions"
  type        = string
  sensitive   = true
}

variable "cloudflare_account_id" {
  description = "Cloudflare account ID"
  type        = string
  sensitive   = true
}

# production_ip is now sourced from terraform_remote_state.production

variable "staging_ip" {
  description = "Staging droplet IP address (pausatf-stage)"
  type        = string
  default     = "REDACTED_STAGE_IP"
}
