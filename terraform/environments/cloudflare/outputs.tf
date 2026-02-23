output "zone_id" {
  description = "Cloudflare zone ID for pausatf.org"
  value       = cloudflare_zone.pausatf.id
}

output "zone_status" {
  description = "Cloudflare zone status"
  value       = cloudflare_zone.pausatf.status
}

output "name_servers" {
  description = "Cloudflare name servers for pausatf.org"
  value       = cloudflare_zone.pausatf.name_servers
}

output "production_records" {
  description = "Production DNS records"
  value = {
    root    = cloudflare_dns_record.root.name
    www     = cloudflare_dns_record.www.name
    ftp     = cloudflare_dns_record.ftp.name
    mail    = cloudflare_dns_record.mail.name
    monitor = cloudflare_dns_record.monitor.name
  }
}

output "staging_records" {
  description = "Staging DNS records"
  value = {
    stage   = cloudflare_dns_record.stage.name
    staging = cloudflare_dns_record.staging.name
  }
}
