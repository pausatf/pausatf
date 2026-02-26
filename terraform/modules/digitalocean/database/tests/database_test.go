package test

import (
	"testing"

	"github.com/gruntwork-io/terratest/modules/terraform"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestDatabaseModuleValidation(t *testing.T) {
	t.Parallel()

	testCases := []struct {
		name          string
		vars          map[string]interface{}
		shouldSucceed bool
	}{
		{
			name: "Valid MySQL configuration",
			vars: map[string]interface{}{
				"test_name":        "test-db-mysql",
				"test_engine":      "mysql",
				"test_environment": "production",
				"test_node_count":  1,
			},
			shouldSucceed: true,
		},
		{
			name: "Valid PostgreSQL configuration",
			vars: map[string]interface{}{
				"test_name":           "test-db-pg",
				"test_engine":         "pg",
				"test_engine_version": "16",
				"test_environment":    "staging",
				"test_node_count":     1,
			},
			shouldSucceed: true,
		},
		{
			name: "Invalid engine",
			vars: map[string]interface{}{
				"test_name":        "test-db-bad",
				"test_engine":      "sqlserver",
				"test_environment": "production",
			},
			shouldSucceed: false,
		},
		{
			name: "Invalid node count (5 nodes)",
			vars: map[string]interface{}{
				"test_name":        "test-db-nodes",
				"test_engine":      "mysql",
				"test_environment": "production",
				"test_node_count":  5,
			},
			shouldSucceed: false,
		},
		{
			name: "Valid redis engine",
			vars: map[string]interface{}{
				"test_name":           "test-db-redis",
				"test_engine":         "redis",
				"test_engine_version": "7",
				"test_environment":    "production",
			},
			shouldSucceed: true,
		},
	}

	for _, tc := range testCases {
		tc := tc // capture range variable
		t.Run(tc.name, func(t *testing.T) {
			t.Parallel()

			terraformOptions := &terraform.Options{
				TerraformDir: "./",
				Vars:         tc.vars,
				EnvVars: map[string]string{
					"DIGITALOCEAN_ACCESS_TOKEN": "fake-token-for-validation",
				},
				NoColor: true,
			}

			if tc.shouldSucceed {
				terraform.InitAndValidate(t, terraformOptions)
			} else {
				_, err := terraform.InitAndValidateE(t, terraformOptions)
				require.Error(t, err, "Expected validation to fail for: %s", tc.name)
				assert.NotNil(t, err)
			}
		})
	}
}
