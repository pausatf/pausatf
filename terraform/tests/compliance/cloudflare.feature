Feature: Cloudflare Configuration Compliance
  As an infrastructure engineer
  I want to ensure Cloudflare configuration follows security best practices
  So that our DNS and CDN are secure

  Scenario: SSL must be set to strict mode
    Given I have cloudflare_zone_settings_override defined
    When it contains settings
    And it contains ssl
    Then its value must be strict

  Scenario: Always use HTTPS must be enabled
    Given I have cloudflare_zone_settings_override defined
    When it contains settings
    And it contains always_use_https
    Then its value must be on

  Scenario: Automatic HTTPS rewrites must be enabled
    Given I have cloudflare_zone_settings_override defined
    When it contains settings
    And it contains automatic_https_rewrites
    Then its value must be on

  Scenario: Minimum TLS version must be 1.2 or higher
    Given I have cloudflare_zone_settings_override defined
    When it contains settings
    And it contains min_tls_version
    Then its value must match the "(1.2|1.3)" regex

  Scenario: HTTP/3 should be enabled for performance
    Given I have cloudflare_zone_settings_override defined
    When it contains settings
    And it contains http3
    Then its value must be on

  Scenario: Brotli compression should be enabled
    Given I have cloudflare_zone_settings_override defined
    When it contains settings
    And it contains brotli
    Then its value must be on

  Scenario: Production domains must not be in development mode
    Given I have cloudflare_zone_settings_override defined
    When it contains settings
    And it contains development_mode
    Then its value must be off

  Scenario: DNS records for production must have comments
    Given I have cloudflare_record defined
    When it contains name
    And its value must match the "pausatf.org|www"
    Then it must contain comment
    And its value must not be null

  Scenario: MX records must have priority
    Given I have cloudflare_record defined
    When it contains type
    And its value must be MX
    Then it must contain priority
    And its value must not be null
