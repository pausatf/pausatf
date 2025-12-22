terraform {
  required_providers {
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 4.20.0"
    }
  }
}

resource "cloudflare_record" "this" {
  for_each = { for record in var.dns_records : "${record.type}-${record.name}" => record }

  zone_id  = var.zone_id
  name     = each.value.name
  type     = each.value.type
  value    = each.value.value
  ttl      = lookup(each.value, "ttl", 1)
  proxied  = lookup(each.value, "proxied", false)
  priority = lookup(each.value, "priority", null)

  comment = lookup(each.value, "comment", "Managed by Terraform")
}
