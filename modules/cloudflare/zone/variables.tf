variable "account_id" {
  description = "Cloudflare account ID"
  type        = string
}

variable "zone_name" {
  description = "Domain name for the zone"
  type        = string
}

variable "plan" {
  description = "Cloudflare plan (free, pro, business, enterprise)"
  type        = string
  default     = "free"
}

variable "type" {
  description = "Zone type (full, partial)"
  type        = string
  default     = "full"
}

# SSL/TLS Settings
variable "ssl_mode" {
  description = "SSL mode (off, flexible, full, strict)"
  type        = string
  default     = "strict"
}

variable "always_use_https" {
  description = "Always use HTTPS"
  type        = string
  default     = "on"
}

variable "automatic_https_rewrites" {
  description = "Enable automatic HTTPS rewrites"
  type        = string
  default     = "on"
}

variable "min_tls_version" {
  description = "Minimum TLS version (1.0, 1.1, 1.2, 1.3)"
  type        = string
  default     = "1.2"
}

variable "tls_1_3_enabled" {
  description = "Enable TLS 1.3"
  type        = bool
  default     = true
}

# Security Settings
variable "security_level" {
  description = "Security level (off, essentially_off, low, medium, high, under_attack)"
  type        = string
  default     = "medium"
}

variable "challenge_ttl" {
  description = "Challenge TTL in seconds"
  type        = number
  default     = 1800
}

variable "browser_check" {
  description = "Enable browser integrity check"
  type        = bool
  default     = true
}

variable "email_obfuscation" {
  description = "Enable email obfuscation"
  type        = bool
  default     = true
}

variable "hotlink_protection" {
  description = "Enable hotlink protection"
  type        = bool
  default     = false
}

# Performance Settings
variable "brotli" {
  description = "Enable Brotli compression"
  type        = bool
  default     = true
}

variable "early_hints" {
  description = "Enable early hints"
  type        = bool
  default     = true
}

variable "http2" {
  description = "Enable HTTP/2"
  type        = bool
  default     = true
}

variable "http3" {
  description = "Enable HTTP/3"
  type        = bool
  default     = true
}

variable "zero_rtt" {
  description = "Enable 0-RTT"
  type        = bool
  default     = true
}

variable "minify_css" {
  description = "Minify CSS"
  type        = bool
  default     = true
}

variable "minify_js" {
  description = "Minify JavaScript"
  type        = bool
  default     = true
}

variable "minify_html" {
  description = "Minify HTML"
  type        = bool
  default     = true
}

# Caching Settings
variable "browser_cache_ttl" {
  description = "Browser cache TTL in seconds"
  type        = number
  default     = 14400
}

# Network Settings
variable "ipv6" {
  description = "Enable IPv6"
  type        = bool
  default     = true
}

variable "websockets" {
  description = "Enable WebSockets"
  type        = bool
  default     = true
}

# DNSSEC
variable "dnssec_enabled" {
  description = "Enable DNSSEC"
  type        = bool
  default     = false
}

# Other
variable "development_mode" {
  description = "Enable development mode (disables caching)"
  type        = bool
  default     = false
}
