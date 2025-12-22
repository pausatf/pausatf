output "record_ids" {
  description = "Map of DNS record IDs"
  value = {
    for k, v in cloudflare_record.this : k => v.id
  }
}

output "record_hostnames" {
  description = "Map of DNS record hostnames"
  value = {
    for k, v in cloudflare_record.this : k => v.hostname
  }
}
