package test

import (
	"fmt"
	"strings"
	"testing"
	"time"

	"github.com/gruntwork-io/terratest/modules/random"
	"github.com/gruntwork-io/terratest/modules/terraform"
	"github.com/stretchr/testify/assert"
)

func TestDropletModule(t *testing.T) {
	t.Parallel()

	// Unique name for this test
	uniqueID := random.UniqueId()
	dropletName := fmt.Sprintf("test-droplet-%s", strings.ToLower(uniqueID))

	terraformOptions := &terraform.Options{
		TerraformDir: "./",
		Vars: map[string]interface{}{
			"test_droplet_name": dropletName,
			"test_region":       "nyc3",
			"test_size":         "s-1vcpu-1gb",
			"test_environment":  "development",
		},
		EnvVars: map[string]string{
			"DIGITALOCEAN_ACCESS_TOKEN": "fake-token-for-testing",
		},
		MaxRetries:         3,
		TimeBetweenRetries: 5 * time.Second,
	}

	// Clean up resources after test
	defer terraform.Destroy(t, terraformOptions)

	// Run terraform init and apply
	terraform.InitAndApply(t, terraformOptions)

	// Validate outputs
	dropletID := terraform.Output(t, terraformOptions, "droplet_id")
	dropletIPv4 := terraform.Output(t, terraformOptions, "droplet_ipv4")
	dropletNameOutput := terraform.Output(t, terraformOptions, "droplet_name")
	dropletRegion := terraform.Output(t, terraformOptions, "droplet_region")
	dropletSize := terraform.Output(t, terraformOptions, "droplet_size")

	// Assertions
	assert.NotEmpty(t, dropletID, "Droplet ID should not be empty")
	assert.NotEmpty(t, dropletIPv4, "Droplet IPv4 should not be empty")
	assert.Equal(t, dropletName, dropletNameOutput, "Droplet name should match")
	assert.Equal(t, "nyc3", dropletRegion, "Droplet region should be nyc3")
	assert.Equal(t, "s-1vcpu-1gb", dropletSize, "Droplet size should be s-1vcpu-1gb")

	// Validate IPv4 format
	assert.Regexp(t, `^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$`, dropletIPv4, "IPv4 should be valid format")
}

func TestDropletModuleValidation(t *testing.T) {
	t.Parallel()

	testCases := []struct {
		name          string
		dropletName   string
		region        string
		size          string
		environment   string
		shouldSucceed bool
	}{
		{
			name:          "Valid configuration",
			dropletName:   "test-droplet-valid",
			region:        "nyc3",
			size:          "s-1vcpu-1gb",
			environment:   "production",
			shouldSucceed: true,
		},
		{
			name:          "Invalid droplet name (uppercase)",
			dropletName:   "Test-Droplet-Invalid",
			region:        "nyc3",
			size:          "s-1vcpu-1gb",
			environment:   "production",
			shouldSucceed: false,
		},
		{
			name:          "Invalid environment",
			dropletName:   "test-droplet",
			region:        "nyc3",
			size:          "s-1vcpu-1gb",
			environment:   "invalid-env",
			shouldSucceed: false,
		},
	}

	for _, tc := range testCases {
		tc := tc // Capture range variable
		t.Run(tc.name, func(t *testing.T) {
			t.Parallel()

			terraformOptions := &terraform.Options{
				TerraformDir: "./",
				Vars: map[string]interface{}{
					"test_droplet_name": tc.dropletName,
					"test_region":       tc.region,
					"test_size":         tc.size,
					"test_environment":  tc.environment,
				},
				EnvVars: map[string]string{
					"DIGITALOCEAN_ACCESS_TOKEN": "fake-token-for-testing",
				},
			}

			if tc.shouldSucceed {
				terraform.Validate(t, terraformOptions)
			} else {
				_, err := terraform.ValidateE(t, terraformOptions)
				assert.Error(t, err, "Validation should fail for invalid configuration")
			}
		})
	}
}
