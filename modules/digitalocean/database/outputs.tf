output "id" {
  description = "ID of the database cluster"
  value       = digitalocean_database_cluster.this.id
}

output "name" {
  description = "Name of the database cluster"
  value       = digitalocean_database_cluster.this.name
}

output "host" {
  description = "Database cluster host"
  value       = digitalocean_database_cluster.this.host
  sensitive   = true
}

output "private_host" {
  description = "Database cluster private host"
  value       = digitalocean_database_cluster.this.private_host
  sensitive   = true
}

output "port" {
  description = "Database cluster port"
  value       = digitalocean_database_cluster.this.port
}

output "uri" {
  description = "Database connection URI"
  value       = digitalocean_database_cluster.this.uri
  sensitive   = true
}

output "private_uri" {
  description = "Database private connection URI"
  value       = digitalocean_database_cluster.this.private_uri
  sensitive   = true
}

output "database" {
  description = "Default database name"
  value       = digitalocean_database_cluster.this.database
}

output "user" {
  description = "Database admin username"
  value       = digitalocean_database_cluster.this.user
  sensitive   = true
}

output "password" {
  description = "Database admin password"
  value       = digitalocean_database_cluster.this.password
  sensitive   = true
}

output "urn" {
  description = "Uniform Resource Name of the database cluster"
  value       = digitalocean_database_cluster.this.urn
}
