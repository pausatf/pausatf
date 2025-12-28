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
    root    = cloudflare_record.root.hostname
    www     = cloudflare_record.www.hostname
    ftp     = cloudflare_record.ftp.hostname
    mail    = cloudflare_record.mail.hostname
    monitor = cloudflare_record.monitor.hostname
  }
}

output "staging_records" {
  description = "Staging DNS records"
  value = {
    stage   = cloudflare_record.stage.hostname
    staging = cloudflare_record.staging.hostname
  }
}

output "dns_record_count" {
  description = "Total number of DNS records managed by Terraform"
  value       = 29
}
