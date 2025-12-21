terraform {
  required_providers {
    digitalocean = {
      source  = "digitalocean/digitalocean"
      version = "~> 2.34.0"
    }
  }
}

resource "digitalocean_database_cluster" "this" {
  name       = var.name
  engine     = var.engine
  version    = var.engine_version
  size       = var.size
  region     = var.region
  node_count = var.node_count

  private_network_uuid = var.vpc_uuid

  maintenance_window {
    day  = var.maintenance_window_day
    hour = var.maintenance_window_hour
  }

  tags = concat(
    var.tags,
    [var.environment]
  )
}

resource "digitalocean_database_firewall" "this" {
  count      = length(var.trusted_sources) > 0 ? 1 : 0
  cluster_id = digitalocean_database_cluster.this.id

  dynamic "rule" {
    for_each = var.trusted_sources
    content {
      type  = rule.value.type
      value = rule.value.value
    }
  }
}

resource "digitalocean_database_db" "databases" {
  for_each   = toset(var.databases)
  cluster_id = digitalocean_database_cluster.this.id
  name       = each.value
}

resource "digitalocean_database_user" "users" {
  for_each   = toset(var.database_users)
  cluster_id = digitalocean_database_cluster.this.id
  name       = each.value
}
