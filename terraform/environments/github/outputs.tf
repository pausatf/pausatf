output "infrastructure_docs_url" {
  description = "Infrastructure docs repository URL"
  value       = module.pausatf_infrastructure_docs.repository_html_url
}

output "terraform_repo_url" {
  description = "Terraform repository URL"
  value       = module.pausatf_terraform.repository_html_url
}

output "ansible_repo_url" {
  description = "Ansible repository URL"
  value       = module.pausatf_ansible.repository_html_url
}

output "scripts_repo_url" {
  description = "Scripts repository URL"
  value       = module.pausatf_scripts.repository_html_url
}

output "repository_names" {
  description = "All repository names"
  value = [
    module.pausatf_infrastructure_docs.repository_name,
    module.pausatf_terraform.repository_name,
    module.pausatf_ansible.repository_name,
    module.pausatf_scripts.repository_name
  ]
}
