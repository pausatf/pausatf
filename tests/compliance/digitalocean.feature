Feature: DigitalOcean Infrastructure Compliance
  As an infrastructure engineer
  I want to ensure all DigitalOcean resources follow security best practices
  So that our infrastructure is secure and compliant

  Scenario: Droplets must have monitoring enabled
    Given I have digitalocean_droplet defined
    Then it must contain monitoring_enabled
    And its value must be true

  Scenario: Production droplets must have backups enabled
    Given I have digitalocean_droplet defined
    When it contains tags
    And it contains production
    Then it must contain backups_enabled
    And its value must be true

  Scenario: Droplets must have IPv6 enabled
    Given I have digitalocean_droplet defined
    Then it must contain ipv6_enabled
    And its value must be true

  Scenario: Droplets must be tagged
    Given I have digitalocean_droplet defined
    Then it must contain tags
    And its value must not be null

  Scenario: Droplets must have valid environment tag
    Given I have digitalocean_droplet defined
    When it contains tags
    Then it must contain environment
    And its value must match the "(production|staging|development)" regex

  Scenario: Database clusters must use private networking
    Given I have digitalocean_database_cluster defined
    Then it must contain private_network_uuid
    And its value must not be null

  Scenario: Database clusters must have maintenance window configured
    Given I have digitalocean_database_cluster defined
    Then it must contain maintenance_window

  Scenario: Firewalls must restrict SSH access
    Given I have digitalocean_firewall defined
    When it contains inbound_rule
    And it contains protocol
    And its value must be tcp
    And it contains port_range
    And its value must match the "22"
    Then it must contain source_addresses
    And its value must not be ["0.0.0.0/0", "::/0"]

  Scenario: Production firewalls must be named appropriately
    Given I have digitalocean_firewall defined
    When it contains name
    Then its value must match the ".*-production$" regex
