output "droplet_id" {
  description = "Production droplet ID"
  value       = digitalocean_droplet.production.id
}

output "droplet_ip" {
  description = "Production droplet public IP"
  value       = digitalocean_droplet.production.ipv4_address
}

output "droplet_urn" {
  description = "Production droplet URN"
  value       = digitalocean_droplet.production.urn
}

output "reserved_ip" {
  description = "Production reserved IP address"
  value       = digitalocean_reserved_ip.production.ip_address
}

output "database_id" {
  description = "Production database cluster ID"
  value       = module.database.id
}

output "database_host" {
  description = "Production database host"
  value       = module.database.host
  sensitive   = true
}

output "database_port" {
  description = "Production database port"
  value       = module.database.port
}

output "database_uri" {
  description = "Production database connection URI"
  value       = module.database.uri
  sensitive   = true
}

output "vpc_id" {
  description = "Production VPC ID"
  value       = digitalocean_vpc.production.id
}

output "firewall_id" {
  description = "Production firewall ID"
  value       = digitalocean_firewall.production.id
}
