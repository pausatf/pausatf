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

output "vpc_id" {
  description = "Production VPC ID"
  value       = digitalocean_vpc.production.id
}

output "firewall_id" {
  description = "Production firewall ID"
  value       = digitalocean_firewall.production.id
}
