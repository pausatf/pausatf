# DigitalOcean Spaces buckets used by PAUSATF. The Spaces provider reads
# SPACES_ACCESS_KEY_ID and SPACES_SECRET_ACCESS_KEY from the environment.
# Imported buckets report no ACL value through the provider. Their live ACLs
# were independently verified as private, so ignore ACL refresh differences
# to avoid a repeated no-op update on every plan.

resource "digitalocean_spaces_bucket" "pausatf" {
  name          = "pausatf"
  region        = "sfo3"
  acl           = "private"
  force_destroy = false

  versioning {
    enabled = true
  }

  lifecycle_rule {
    id                                     = "pausatf-backup-version-retention"
    enabled                                = true
    abort_incomplete_multipart_upload_days = 7

    noncurrent_version_expiration {
      days = 60
    }
  }

  lifecycle_rule {
    id      = "pausatf-prod-daily-backups-45d"
    prefix  = "backups/prod/"
    enabled = true

    expiration {
      days = 45
    }
  }

  lifecycle_rule {
    id      = "pausatf-recovery-images-60d"
    prefix  = "backups/recovery/images/"
    enabled = true

    expiration {
      days = 60
    }
  }

  lifecycle_rule {
    id      = "pausatf-recovery-sets-45d"
    prefix  = "backups/recovery/sets/"
    enabled = true

    expiration {
      days = 45
    }
  }

  lifecycle {
    prevent_destroy = true
    ignore_changes  = [acl]
  }
}

resource "digitalocean_spaces_bucket" "pausatf_static" {
  name          = "pausatf-static"
  region        = "sfo3"
  acl           = "private"
  force_destroy = false

  versioning {
    enabled = true
  }

  lifecycle_rule {
    id                                     = "pausatf-upload-version-recovery"
    enabled                                = true
    abort_incomplete_multipart_upload_days = 7

    noncurrent_version_expiration {
      days = 60
    }
  }

  lifecycle {
    prevent_destroy = true
    ignore_changes  = [acl]
  }
}

resource "digitalocean_spaces_bucket" "pausatf_backups" {
  name          = "pausatf-backups"
  region        = "sfo2"
  acl           = "private"
  force_destroy = false

  versioning {
    enabled = true
  }

  lifecycle_rule {
    id      = "expire-dev"
    prefix  = "dev/"
    enabled = true

    expiration {
      days = 30
    }
  }

  lifecycle_rule {
    id                                     = "expire-noncurrent-backups"
    prefix                                 = ""
    enabled                                = true
    abort_incomplete_multipart_upload_days = 7

    noncurrent_version_expiration {
      days = 60
    }
  }

  lifecycle_rule {
    id      = "expire-production"
    prefix  = "production/"
    enabled = true

    expiration {
      days = 60
    }
  }

  lifecycle_rule {
    id      = "expire-staging"
    prefix  = "staging/"
    enabled = true

    expiration {
      days = 60
    }
  }

  lifecycle {
    prevent_destroy = true
    ignore_changes  = [acl]
  }
}

resource "digitalocean_spaces_bucket" "terraform_state_archive" {
  name          = "pausatf-terraform-state"
  region        = "sfo2"
  acl           = "private"
  force_destroy = false

  versioning {
    enabled = true
  }

  lifecycle {
    prevent_destroy = true
    ignore_changes  = [acl]
  }
}
