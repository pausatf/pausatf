terraform {
  required_providers {
    digitalocean = {
      source  = "digitalocean/digitalocean"
      version = "~> 2.69"
    }
  }
}

resource "digitalocean_droplet" "this" {
  name   = var.name
  region = var.region
  size   = var.size
  image  = var.image

  ssh_keys = var.ssh_key_ids

  vpc_uuid = var.vpc_uuid

  monitoring = var.monitoring_enabled
  backups    = var.backups_enabled
  ipv6       = var.ipv6_enabled

  user_data = var.user_data

  tags = concat(
    var.tags,
    [var.environment]
  )

  lifecycle {
    create_before_destroy = true
    ignore_changes = [
      user_data, # Ignore user_data changes after initial creation
    ]
  }
}

# Attach droplet to firewall
resource "digitalocean_firewall" "droplet_firewall" {
  count = var.firewall_id != null ? 1 : 0

  name = "${var.name}-firewall"

  droplet_ids = [digitalocean_droplet.this.id]

  dynamic "inbound_rule" {
    for_each = var.firewall_inbound_rules
    content {
      protocol         = inbound_rule.value.protocol
      port_range       = lookup(inbound_rule.value, "port_range", null)
      source_addresses = lookup(inbound_rule.value, "source_addresses", ["0.0.0.0/0", "::/0"])
    }
  }

  dynamic "outbound_rule" {
    for_each = var.firewall_outbound_rules
    content {
      protocol              = outbound_rule.value.protocol
      port_range            = lookup(outbound_rule.value, "port_range", null)
      destination_addresses = lookup(outbound_rule.value, "destination_addresses", ["0.0.0.0/0", "::/0"])
    }
  }
}
