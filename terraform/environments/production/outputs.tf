output "droplet_id" {
  description = "Production droplet ID"
  value       = module.wordpress.droplet_id
}

output "droplet_ip" {
  description = "Production droplet public IP"
  value       = module.wordpress.droplet_ip
}

output "droplet_urn" {
  description = "Production droplet URN"
  value       = module.wordpress.droplet_urn
}

output "reserved_ip" {
  description = "Production reserved IP address"
  value       = digitalocean_reserved_ip.production.ip_address
}

output "database_id" {
  description = "Production database cluster ID"
  value       = module.wordpress.database_id
}

output "database_host" {
  description = "Production database host"
  value       = module.wordpress.database_host
  sensitive   = true
}

output "database_port" {
  description = "Production database port"
  value       = module.wordpress.database_port
}

output "database_uri" {
  description = "Production database connection URI"
  value       = module.wordpress.database_uri
  sensitive   = true
}

output "vpc_id" {
  description = "Production VPC ID"
  value       = module.wordpress.vpc_id
}

output "firewall_id" {
  description = "Production firewall ID"
  value       = module.wordpress.firewall_id
}
