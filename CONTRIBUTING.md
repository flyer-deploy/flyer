# Contribute

Since Flyer will go to production soon, it needs to have proper contribution standards.

## For contributors

- Any features, bug fixes, etc are pushed to its own branch. The branch name cannot be in either of these:

  - release-\*
  - master

- Create pull request to `main` if you want to integrate the changes you made

- If possible, it is extremely encouraged to create the test case for the new feature. You can either:

  - Add test case in the existing `tests/test-cases.yaml` file

  - Create new test case file inside `tests` directory

  - Create new test code inside `tests` directory

- Reflect the changes in all of the docs (internal or user guide)

## For admins/leads

- For each release, create new branch with the name `release-${version}`. The version uses semantic versioning

- Lock this branch so no more pushes are allowed. Future merges still go to `main`
