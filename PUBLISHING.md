# Publish to GitHub — exact order

1. Create a **public, empty** repository at <https://github.com/new> named `opsdesk-api`. Keep README, .gitignore and License **unchecked**; the project includes these files.
2. Extract the ZIP. Open the **inner `opsdesk-api` directory** in VS Code. You must see `README.md`, `composer.json` and `docker-compose.yml` beside one another.
3. In the VS Code terminal, run `git init`, `git add .`, `git commit -m "feat: launch opsdesk laravel API"`, `git branch -M main`.
4. Run `git remote add origin https://github.com/hirques/opsdesk-api.git` and `git push -u origin main`.
5. Check the README on GitHub and open the **Actions** tab for the test job.
6. On your PC with Docker Desktop, run `.\scripts\setup.ps1` (PowerShell) in the project's root. Then open <http://localhost:8080/docs/>.

**Do not commit `.env`.** If you accidentally commit secrets, revoke and rotate them; simply removing a later commit does not remove them from Git history.

This archive was syntax/static-checked without Composer dependencies or Docker in the builder environment. Runtime and GitHub CI results must be confirmed on your computer/GitHub.
