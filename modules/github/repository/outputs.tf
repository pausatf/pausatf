output "repository_id" {
  description = "GitHub repository ID"
  value       = github_repository.repo.repo_id
}

output "repository_node_id" {
  description = "GitHub repository node ID"
  value       = github_repository.repo.node_id
}

output "repository_name" {
  description = "Repository name"
  value       = github_repository.repo.name
}

output "repository_full_name" {
  description = "Repository full name (owner/repo)"
  value       = github_repository.repo.full_name
}

output "repository_html_url" {
  description = "Repository URL"
  value       = github_repository.repo.html_url
}

output "repository_ssh_clone_url" {
  description = "SSH clone URL"
  value       = github_repository.repo.ssh_clone_url
}

output "repository_http_clone_url" {
  description = "HTTP clone URL"
  value       = github_repository.repo.http_clone_url
}

output "repository_git_clone_url" {
  description = "Git clone URL"
  value       = github_repository.repo.git_clone_url
}

output "default_branch" {
  description = "Default branch name"
  value       = github_repository.repo.default_branch
}
