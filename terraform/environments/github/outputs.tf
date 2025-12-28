output "repository_id" {
  description = "PAUSATF monorepo repository ID"
  value       = module.pausatf_monorepo.repository_id
}

output "repository_url" {
  description = "PAUSATF monorepo URL"
  value       = module.pausatf_monorepo.repository_html_url
}

output "repository_name" {
  description = "PAUSATF monorepo name"
  value       = module.pausatf_monorepo.repository_name
}

output "repository_full_name" {
  description = "PAUSATF monorepo full name (owner/repo)"
  value       = module.pausatf_monorepo.repository_full_name
}

output "ssh_clone_url" {
  description = "SSH clone URL"
  value       = module.pausatf_monorepo.repository_ssh_clone_url
}

output "http_clone_url" {
  description = "HTTP clone URL"
  value       = module.pausatf_monorepo.repository_http_clone_url
}

output "default_branch" {
  description = "Default branch name"
  value       = module.pausatf_monorepo.default_branch
}
