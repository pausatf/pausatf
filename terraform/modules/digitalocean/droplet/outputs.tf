output "id" {
  description = "ID of the droplet"
  value       = digitalocean_droplet.this.id
}

output "name" {
  description = "Name of the droplet"
  value       = digitalocean_droplet.this.name
}

output "ipv4_address" {
  description = "Public IPv4 address of the droplet"
  value       = digitalocean_droplet.this.ipv4_address
}

output "ipv4_address_private" {
  description = "Private IPv4 address of the droplet"
  value       = digitalocean_droplet.this.ipv4_address_private
}

output "ipv6_address" {
  description = "Public IPv6 address of the droplet"
  value       = digitalocean_droplet.this.ipv6_address
}

output "urn" {
  description = "Uniform Resource Name (URN) of the droplet"
  value       = digitalocean_droplet.this.urn
}

output "region" {
  description = "Region of the droplet"
  value       = digitalocean_droplet.this.region
}

output "size" {
  description = "Size of the droplet"
  value       = digitalocean_droplet.this.size
}

output "tags" {
  description = "Tags applied to the droplet"
  value       = digitalocean_droplet.this.tags
}
