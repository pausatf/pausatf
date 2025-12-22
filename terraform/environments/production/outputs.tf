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

output "database_id" {
  description = "Production database cluster ID"
  value       = digitalocean_database_cluster.production.id
}

output "database_host" {
  description = "Production database host"
  value       = digitalocean_database_cluster.production.host
  sensitive   = true
}

output "database_port" {
  description = "Production database port"
  value       = digitalocean_database_cluster.production.port
}

output "database_uri" {
  description = "Production database connection URI"
  value       = digitalocean_database_cluster.production.uri
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
