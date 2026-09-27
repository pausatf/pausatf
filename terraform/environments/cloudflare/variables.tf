variable "cloudflare_api_token" {
  description = "Cloudflare API token with Zone.DNS and Zone.Zone permissions"
  type        = string
  sensitive   = true
}

variable "cloudflare_account_id" {
  description = "Cloudflare account ID"
  type        = string
  default     = "c540729070ba913814ac4557c8974099" # pragma: allowlist secret
}
