output "staging_ipv4" {
  description = "IPv4 of staging droplet"
  value       = digitalocean_droplet.staging.ipv4_address
}

output "stage_dns_records" {
  description = "Created DNS records for staging"
  value       = module.cloudflare_dns_staging.record_hostnames
}

output "droplet_id" {
  description = "Staging droplet ID"
  value       = digitalocean_droplet.staging.id
}

output "droplet_ip" {
  description = "Staging droplet public IP"
  value       = digitalocean_droplet.staging.ipv4_address
}

output "database_id" {
  description = "Staging database cluster ID"
  value       = digitalocean_database_cluster.staging.id
}

output "database_host" {
  description = "Staging database host"
  value       = digitalocean_database_cluster.staging.host
  sensitive   = true
}

output "database_uri" {
  description = "Staging database connection URI"
  value       = digitalocean_database_cluster.staging.uri
  sensitive   = true
}

output "vpc_id" {
  description = "Staging VPC ID"
  value       = digitalocean_vpc.staging.id
}

output "firewall_id" {
  description = "Staging firewall ID"
  value       = digitalocean_firewall.staging.id
}
