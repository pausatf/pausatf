# Task 2 Implementation Plan

## .github/workflows/ci.yml
Replace the existing `ansible-lint` job with:
```yaml
  ansible-lint:
    name: Ansible Lint
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run ansible-lint
        uses: ansible/ansible-lint@main
        with:
          path: "ansible/"
```

## .github/workflows/molecule.yml
Replace the existing `molecule` job with:
```yaml
  molecule:
    name: molecule / common
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Set up Python
        uses: actions/setup-python@v5
        with:
          python-version: '3.11'
      - name: Install dependencies
        run: |
          python -m pip install --upgrade pip
          pip install molecule molecule-plugins[docker] ansible-lint
      - name: Run Molecule tests
        run: |
          cd ansible/roles/common
          molecule test
        env:
          PY_COLORS: '1'
          ANSIBLE_FORCE_COLOR: '1'
```
