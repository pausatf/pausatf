# DigitalOcean Database Module

Terraform module for creating and managing DigitalOcean managed database clusters with support for MySQL, PostgreSQL, Redis, MongoDB, Kafka, and OpenSearch.

## Features

- **Multi-Engine Support**: MySQL, PostgreSQL, Redis, MongoDB, Kafka, OpenSearch
- **High Availability**: 1-3 node clusters with automatic failover
- **Firewall Management**: Built-in database firewall with trusted sources
- **VPC Integration**: Private networking support
- **Automatic Backups**: Daily backups included
- **Maintenance Windows**: Configurable maintenance schedules
- **Multi-Database**: Create multiple databases in a single cluster
- **Multi-User**: Manage multiple database users
- **Connection Pooling**: Built-in connection pooling for supported engines

## Usage

### Basic MySQL Database

```hcl
module "mysql_db" {
  source = "../../modules/digitalocean/database"

  name            = "pausatf-db"
  engine          = "mysql"
  engine_version  = "8"
  size            = "db-s-1vcpu-1gb"
  region          = "sfo3"
  node_count      = 1
  environment     = "production"

  # Maintenance window (Sunday at 2 AM)
  maintenance_window_day  = "sunday"
  maintenance_window_hour = "02:00"

  tags = ["production", "wordpress"]
}
```

### High-Availability PostgreSQL

```hcl
module "postgres_ha" {
  source = "../../modules/digitalocean/database"

  name            = "app-db"
  engine          = "pg"
  engine_version  = "15"
  size            = "db-s-2vcpu-4gb"
  region          = "sfo3"
  node_count      = 3  # High availability with 3 nodes
  environment     = "production"

  vpc_uuid = digitalocean_vpc.main.id

  # Firewall: Only allow specific droplets
  trusted_sources = [
    {
      type  = "droplet"
      value = module.web_server.id
    },
    {
      type  = "tag"
      value = "app-servers"
    }
  ]

  # Create multiple databases
  databases = ["app_prod", "app_analytics"]

  # Create additional users
  database_users = ["app_user", "readonly_user"]

  tags = ["production", "ha"]
}
```

### Redis Cache

```hcl
module "redis_cache" {
  source = "../../modules/digitalocean/database"

  name            = "app-cache"
  engine          = "redis"
  engine_version  = "7"
  size            = "db-s-1vcpu-1gb"
  region          = "sfo3"
  node_count      = 1
  environment     = "production"

  vpc_uuid = digitalocean_vpc.main.id

  trusted_sources = [
    {
      type  = "tag"
      value = "web-servers"
    }
  ]

  tags = ["cache", "production"]
}
```

## Requirements

| Name | Version |
|------|---------|
| terraform | >= 1.0 |
| digitalocean | ~> 2.69 |

## Inputs

### Required Inputs

| Name | Description | Type |
|------|-------------|------|
| name | Name of the database cluster | `string` |
| environment | Environment name | `string` |

### Optional Inputs

#### Database Configuration

| Name | Description | Type | Default |
|------|-------------|------|---------|
| engine | Database engine (mysql, pg, redis, mongodb, kafka, opensearch) | `string` | `"mysql"` |
| engine_version | Version of the database engine | `string` | `"8"` |
| size | Database cluster size/tier | `string` | `"db-s-1vcpu-1gb"` |
| region | DigitalOcean region | `string` | `"sfo2"` |
| node_count | Number of nodes in the cluster (1, 2, or 3) | `number` | `1` |

#### Network Configuration

| Name | Description | Type | Default |
|------|-------------|------|---------|
| vpc_uuid | UUID of the VPC for private networking | `string` | `null` |

#### Maintenance

| Name | Description | Type | Default |
|------|-------------|------|---------|
| maintenance_window_day | Day of week for maintenance (monday-sunday) | `string` | `"sunday"` |
| maintenance_window_hour | Hour for maintenance window (00:00-23:00) | `string` | `"02:00"` |

#### Security

| Name | Description | Type | Default |
|------|-------------|------|---------|
| trusted_sources | List of trusted sources for database firewall | `list(object)` | `[]` |

#### Database Management

| Name | Description | Type | Default |
|------|-------------|------|---------|
| databases | List of databases to create | `list(string)` | `[]` |
| database_users | List of database users to create | `list(string)` | `[]` |

#### Other

| Name | Description | Type | Default |
|------|-------------|------|---------|
| tags | List of tags | `list(string)` | `[]` |

### Trusted Source Object Structure

```hcl
{
  type  = string  # ip_addr, droplet, k8s, tag
  value = string  # IP address, droplet ID, k8s cluster ID, or tag name
}
```

## Outputs

| Name | Description | Sensitive |
|------|-------------|-----------|
| id | ID of the database cluster | No |
| name | Name of the database cluster | No |
| host | Database cluster host | Yes |
| private_host | Database cluster private host | Yes |
| port | Database cluster port | No |
| uri | Database connection URI | Yes |
| private_uri | Database private connection URI | Yes |
| database | Default database name | No |
| user | Database admin username | Yes |
| password | Database admin password | Yes |
| urn | Uniform Resource Name of the database cluster | No |

## Examples

### Production WordPress Database

```hcl
module "wordpress_db" {
  source = "../../modules/digitalocean/database"

  name            = "pausatf-prod-db"
  engine          = "mysql"
  engine_version  = "8"
  size            = "db-s-2vcpu-4gb"
  region          = "sfo3"
  node_count      = 1
  environment     = "production"

  vpc_uuid = digitalocean_vpc.main.id

  # Only allow production web server
  trusted_sources = [
    {
      type  = "droplet"
      value = "355909945"  # pausatf-prod droplet ID
    }
  ]

  databases = ["wordpress_prod"]

  maintenance_window_day  = "sunday"
  maintenance_window_hour = "03:00"

  tags = ["wordpress", "production", "pausatf"]
}
```

### MongoDB for Application Data

```hcl
module "app_mongodb" {
  source = "../../modules/digitalocean/database"

  name            = "app-mongodb"
  engine          = "mongodb"
  engine_version  = "6"
  size            = "db-s-4vcpu-8gb"
  region          = "sfo3"
  node_count      = 3  # Replica set
  environment     = "production"

  vpc_uuid = digitalocean_vpc.main.id

  trusted_sources = [
    {
      type  = "tag"
      value = "app-servers"
    }
  ]

  tags = ["mongodb", "production"]
}
```

### Using Connection Credentials

```hcl
# Store credentials in secrets manager (recommended)
resource "vault_generic_secret" "db_creds" {
  path = "secret/database/production"

  data_json = jsonencode({
    host     = module.wordpress_db.private_host
    port     = module.wordpress_db.port
    database = module.wordpress_db.database
    user     = module.wordpress_db.user
    password = module.wordpress_db.password
    uri      = module.wordpress_db.private_uri
  })
}

# Or use in application config (less secure)
resource "local_file" "db_config" {
  filename        = "db-config.env"
  file_permission = "0600"

  content = <<-EOT
    DB_HOST=${module.wordpress_db.private_host}
    DB_PORT=${module.wordpress_db.port}
    DB_NAME=${module.wordpress_db.database}
    DB_USER=${module.wordpress_db.user}
    DB_PASSWORD=${module.wordpress_db.password}
  EOT

  sensitive_content = true
}
```

## Supported Engines and Versions

### MySQL
- **Versions**: 8
- **Use Cases**: WordPress, web applications, OLTP
- **Features**: ACID compliance, replication, partitioning

### PostgreSQL (pg)
- **Versions**: 12, 13, 14, 15, 16
- **Use Cases**: Complex queries, analytics, geospatial data
- **Features**: JSON support, full-text search, advanced indexing

### Redis
- **Versions**: 6, 7
- **Use Cases**: Caching, session storage, real-time analytics
- **Features**: In-memory, pub/sub, data structures

### MongoDB
- **Versions**: 4, 5, 6, 7
- **Use Cases**: Document storage, flexible schemas
- **Features**: JSON documents, horizontal scaling, aggregation

### Kafka
- **Versions**: 3.5, 3.6
- **Use Cases**: Event streaming, message queuing
- **Features**: High throughput, fault tolerance

### OpenSearch
- **Versions**: 1, 2
- **Use Cases**: Full-text search, log analytics
- **Features**: RESTful API, distributed search

## Database Cluster Sizes

| Size | vCPUs | RAM | Disk | Monthly Cost (approx) |
|------|-------|-----|------|------------------------|
| db-s-1vcpu-1gb | 1 | 1 GB | 10 GB | $15/month |
| db-s-1vcpu-2gb | 1 | 2 GB | 25 GB | $30/month |
| db-s-2vcpu-4gb | 2 | 4 GB | 38 GB | $60/month |
| db-s-4vcpu-8gb | 4 | 8 GB | 115 GB | $120/month |
| db-s-6vcpu-16gb | 6 | 16 GB | 270 GB | $240/month |
| db-s-8vcpu-32gb | 8 | 32 GB | 580 GB | $480/month |

**Note**: Costs multiply by node_count for HA configurations.

## High Availability

| Node Count | Configuration | Use Case |
|------------|---------------|----------|
| 1 | Single node | Development, testing, low-traffic |
| 2 | Primary + standby | Production with failover |
| 3 | Primary + 2 standbys | High availability, mission-critical |

**HA Benefits:**
- Automatic failover
- Zero-downtime maintenance
- Read replicas (for some engines)
- Geographic redundancy

## Firewall Trusted Source Types

### 1. IP Address (ip_addr)
```hcl
{
  type  = "ip_addr"
  value = "192.168.1.100"
}
```

### 2. Droplet (droplet)
```hcl
{
  type  = "droplet"
  value = "355909945"  # Droplet ID
}
```

### 3. Kubernetes (k8s)
```hcl
{
  type  = "k8s"
  value = "cluster-uuid"
}
```

### 4. Tag (tag)
```hcl
{
  type  = "tag"
  value = "web-servers"
}
```

## Best Practices

1. **Use VPC**: Always enable private networking for security
2. **Firewall**: Restrict access to only necessary droplets/IPs
3. **High Availability**: Use 3 nodes for production databases
4. **Maintenance Window**: Schedule during low-traffic periods
5. **Backups**: Daily backups are automatic and included
6. **Connection Pooling**: Use private_uri for VPC connections
7. **Monitoring**: Enable alerts for disk usage, CPU, memory
8. **Credentials**: Store passwords in secrets manager, never in code
9. **Scaling**: Monitor and scale size before hitting limits
10. **Read Replicas**: Use for read-heavy workloads (check engine support)

## Security Considerations

1. **Never expose database publicly**: Always use VPC and firewall
2. **Rotate credentials**: Change passwords periodically
3. **Least privilege**: Create users with minimum required permissions
4. **SSL/TLS**: Enable encryption in transit (enabled by default)
5. **Audit logs**: Enable and monitor database audit logs
6. **Compliance**: Use encryption at rest (enabled by default)

## Backup and Recovery

- **Automatic backups**: Daily backups retained for 7 days
- **Point-in-time recovery**: Available for most engines
- **Manual backups**: Can be triggered via API/UI
- **Restoration**: Restore to new cluster, test, then migrate

## Performance Tuning

1. **Connection Pooling**: Use built-in pooling for supported engines
2. **Indexing**: Create appropriate indexes for queries
3. **Caching**: Use Redis for frequently accessed data
4. **Query Optimization**: Monitor slow queries and optimize
5. **Scaling**: Vertical (bigger size) or horizontal (more nodes)

## Monitoring Metrics

Monitor these key metrics:
- CPU usage
- Memory usage
- Disk usage
- Connection count
- Query performance
- Replication lag (HA clusters)

## Troubleshooting

### Issue: Connection refused

**Solution**:
1. Check firewall trusted sources include your droplet/IP
2. Verify VPC configuration if using private network
3. Ensure database cluster is in "online" state

### Issue: Slow queries

**Solution**:
1. Review slow query logs
2. Add appropriate indexes
3. Consider scaling to larger size
4. Enable connection pooling

### Issue: Out of connections

**Solution**:
1. Increase connection pool size
2. Fix connection leaks in application
3. Scale to larger database size

## Cost Optimization

1. **Right-sizing**: Start small, scale up as needed
2. **Development**: Use single-node clusters for dev/staging
3. **Reserved instances**: Consider reserved pricing for production
4. **Storage**: Monitor and clean up unused data
5. **HA**: Only use 3-node clusters when truly needed

## Migration Guide

### From Self-Managed to Managed

1. Create new managed database cluster
2. Export data from current database
3. Import data to managed cluster
4. Update application connection strings
5. Test thoroughly
6. Switch DNS/load balancer to new database
7. Decommission old database

## Related Modules

- [digitalocean/droplet](../droplet) - Compute instances that connect to database

## Version History

- **2025-12-28**: Updated to DigitalOcean provider ~> 2.69
- **2025-12-22**: Initial module creation

## Maintainer

PAUSATF Infrastructure Team
