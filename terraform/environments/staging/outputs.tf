output "staging_ipv4" {
  description = "IPv4 of staging droplet"
  value       = module.wordpress.droplet_ip
}

output "stage_dns_records" {
  description = "Created DNS records for staging"
  value       = module.cloudflare_dns_staging.record_hostnames
}

output "droplet_id" {
  description = "Staging droplet ID"
  value       = module.wordpress.droplet_id
}

output "droplet_ip" {
  description = "Staging droplet public IP"
  value       = module.wordpress.droplet_ip
}

output "database_id" {
  description = "Staging database cluster ID"
  value       = module.wordpress.database_id
}

output "database_host" {
  description = "Staging database host"
  value       = module.wordpress.database_host
  sensitive   = true
}

output "database_uri" {
  description = "Staging database connection URI"
  value       = module.wordpress.database_uri
  sensitive   = true
}

output "firewall_id" {
  description = "Staging firewall ID"
  value       = module.wordpress.firewall_id
}
