terraform {
  required_version = ">= 1.10.0"

  required_providers {
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 5.17"
    }
  }
}

resource "cloudflare_dns_record" "this" {
  for_each = { for record in var.dns_records : "${record.type}-${record.name}" => record }

  zone_id  = var.zone_id
  name     = each.value.name
  type     = each.value.type
  content  = each.value.value
  ttl      = lookup(each.value, "ttl", 1)
  proxied  = lookup(each.value, "proxied", false)
  priority = lookup(each.value, "priority", null)

  comment = lookup(each.value, "comment", "Managed by Terraform")
}
