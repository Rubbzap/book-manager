# BookShelf Library

PHP + MySQL book borrowing web app with user accounts, Gmail requirement, timed loans, cover thumbnails, and bilingual UI.

## Run locally

```bash
docker compose up -d --build
```

- App: http://localhost:8080
- phpMyAdmin: http://localhost:8081

Default admin:

- Username: `admin`
- Password: `admin1234`

## Publish to GitHub

Install Git first, then run:

```bash
git init
git add .
git commit -m "Build BookShelf Library app"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git push -u origin main
```

This project cannot run on GitHub Pages because it needs PHP and MySQL. Deploy it to a Docker-capable host such as Render, Railway, a VPS, or your own server.

## Railway database variables

Add a MySQL service in the same Railway project, then set the web service variables from the MySQL service:

```text
MYSQL_URL=${{MySQL.MYSQL_URL}}
```

If you prefer separate variables, set:

```text
MYSQLHOST=${{MySQL.MYSQLHOST}}
MYSQLPORT=${{MySQL.MYSQLPORT}}
MYSQLUSER=${{MySQL.MYSQLUSER}}
MYSQLPASSWORD=${{MySQL.MYSQLPASSWORD}}
MYSQLDATABASE=${{MySQL.MYSQLDATABASE}}
```

After changing variables, redeploy the web service.
