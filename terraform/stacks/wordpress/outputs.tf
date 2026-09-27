output "reserved_ip_address" {
  description = "Reserved IP address for this environment"
  value       = var.create_reserved_ip ? digitalocean_reserved_ip.this[0].ip_address : null
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
  value       = var.create_database ? module.database[0].host : null
  sensitive   = true
}

output "database_private_host" {
  description = "Managed database private host (VPC)"
  value       = var.create_database ? module.database[0].private_host : null
  sensitive   = true
}

output "database_port" {
  description = "Managed database port"
  value       = var.create_database ? module.database[0].port : null
}

output "database_uri" {
  description = "Managed database connection URI"
  value       = var.create_database ? module.database[0].uri : null
  sensitive   = true
}

output "database_id" {
  description = "Managed database cluster ID"
  value       = var.create_database ? module.database[0].id : null
}

output "database_urn" {
  description = "Managed database cluster URN"
  value       = var.create_database ? module.database[0].urn : null
}

output "database_user" {
  description = "Managed database admin username"
  value       = var.create_database ? module.database[0].user : null
  sensitive   = true
}

output "database_password" {
  description = "Managed database admin password"
  value       = var.create_database ? module.database[0].password : null
  sensitive   = true
}

output "database_name" {
  description = "Managed database default database name"
  value       = var.create_database ? module.database[0].database : null
}

output "vpc_id" {
  description = "VPC ID"
  value       = var.create_vpc ? digitalocean_vpc.this[0].id : data.digitalocean_vpc.existing[0].id
}

output "firewall_id" {
  description = "Firewall ID"
  value       = digitalocean_firewall.this.id
}
