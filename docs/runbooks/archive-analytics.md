# Archive analytics

Historical pages load `/data/pausatf-legacy-analytics.js` from the same origin.
The shared loader uses the existing Google and Quantcast property IDs and HTTPS SDK URLs.
It loads SDKs asynchronously and initializes each provider after its script loads.
Existing callers outside the tracking blocks do not use the tracker globals.

The `data-provider` values are `google`, `google-pageview`, `google-library`,
`quantcast`, and `quantcast-options`. These select fixed implementations, never executable URLs.
The Google variants retain the original initialization, pageview-only, or library-only behavior.
Quantcast pages retain an HTTPS beacon for browsers with JavaScript disabled.

Twelve nonstandard archive files retain their inline code and original execution order.
They contain downloaded SDK references, unusual HTML comment boundaries, or interleaved scripts.
Their explicit Google and Quantcast remote URLs use HTTPS. They remain covered by the archive tests.
No Sonar exclusions or quality-gate thresholds are changed for these files.

## Verification

Run the maintenance tests from a checkout containing every tracked `content/` file.
The archive regression executes the shared loader with controlled SDKs, verifies all provider references,
checks retained HTTPS selectors, and tests unavailable SDKs and unrecognized provider identifiers.
Use the [deployment runbook](deployment.md) for source review and target selection.

## Deployment and rollback

Merging this change does not deploy the archive. Use its actual Docker-origin static-content owner;
do not apply the retired host web-stack playbook to the container origin.
Upload the shared JavaScript asset before publishing HTML that references it.
Keep the previous archive files and record the reviewed source revision and checksums.
After deployment, verify the asset response, representative results pages, and tracking initialization.
Provider availability and acceptance of legacy Google property IDs require separate operational verification.

For rollback, restore the previous HTML first. Retain the shared asset while cached HTML can reference it.
Historical results, labels, links unrelated to telemetry, and original file encodings must remain intact.
