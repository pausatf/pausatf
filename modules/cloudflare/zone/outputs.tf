output "zone_id" {
  description = "Zone ID"
  value       = cloudflare_zone.this.id
}

output "zone_name" {
  description = "Zone name"
  value       = cloudflare_zone.this.zone
}

output "name_servers" {
  description = "Cloudflare nameservers"
  value       = cloudflare_zone.this.name_servers
}

output "status" {
  description = "Zone status"
  value       = cloudflare_zone.this.status
}

output "verification_key" {
  description = "Zone verification key"
  value       = cloudflare_zone.this.verification_key
  sensitive   = true
}
