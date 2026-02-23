output "reserved_ip_address" {
  description = "Reserved IP address for this environment"
  value       = digitalocean_reserved_ip.this.ip_address
}

output "droplet_id" {
  description = "Droplet ID"
  value       = digitalocean_droplet.this.id
}

output "droplet_ip" {
  description = "Droplet public IPv4 address"
  value       = digitalocean_droplet.this.ipv4_address
}

output "droplet_urn" {
  description = "Droplet URN"
  value       = digitalocean_droplet.this.urn
}

output "database_host" {
  description = "Managed database host"
  value       = module.database.host
  sensitive   = true
}

output "database_private_host" {
  description = "Managed database private host (VPC)"
  value       = module.database.private_host
  sensitive   = true
}

output "database_port" {
  description = "Managed database port"
  value       = module.database.port
}

output "database_uri" {
  description = "Managed database connection URI"
  value       = module.database.uri
  sensitive   = true
}

output "database_id" {
  description = "Managed database cluster ID"
  value       = module.database.id
}

output "database_urn" {
  description = "Managed database cluster URN"
  value       = module.database.urn
}

output "vpc_id" {
  description = "VPC ID"
  value       = digitalocean_vpc.this.id
}

output "firewall_id" {
  description = "Firewall ID"
  value       = digitalocean_firewall.this.id
}
