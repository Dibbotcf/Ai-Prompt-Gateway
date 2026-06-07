# AI Prompt Security Gateway — Coolify Deployment Notes

> **This file is your single source of truth for all Coolify deployment info.**
> Next time you want to push an update, just do `git push origin main` — CI/CD handles the rest.

---

## 🌐 Live URL

```
https://aiprompt.astrozupi.com
```

---

## 🔑 Coolify Credentials

| Field        | Value                              |
|--------------|------------------------------------|
| **Panel URL**    | https://deploy.oryzen.app          |
| **Email**        | quaziehsanh@gmail.com              |
| **Password**     | ehsl~!u7<uWu'17NQGYZgX            |

### Direct Project Links
| Resource | URL |
|---|---|
| **Project** | https://deploy.oryzen.app/project/aw8w1941qjjtsr6ok8v68twq |
| **Environment** | https://deploy.oryzen.app/project/aw8w1941qjjtsr6ok8v68twq/environment/zbpmm5b0w... |

---

## 🐙 GitHub Repository

| Field    | Value                                              |
|----------|----------------------------------------------------|
| **Repo** | https://github.com/Dibbotcf/Ai-Prompt-Gateway     |
| **Branch** | `main`                                           |
| **Username** | `Dibbotcf`                                     |
| **Password/Token** | `dibbo.tcfbd@06`                         |

---

## 🗄️ Database (MySQL — Coolify Managed)

> **Fill this in after adding the MySQL service in Coolify.**
> Go to: Project → + New Resource → Database → MySQL

| Field         | Value                    |
|---------------|--------------------------|
| **DB_HOST**   | *(from Coolify MySQL service)* |
| **DB_PORT**   | `3306`                   |
| **DB_NAME**   | `prompt_gateway`         |
| **DB_USER**   | *(from Coolify MySQL service)* |
| **DB_PASS**   | *(from Coolify MySQL service)* |

---

## ⚙️ Environment Variables (set in Coolify App → Environment Variables tab)

```env
DB_HOST=<coolify-mysql-host>
DB_PORT=3306
DB_NAME=prompt_gateway
DB_USER=<coolify-mysql-user>
DB_PASS=<coolify-mysql-password>
```

---

## 🏗️ Build Configuration

| Setting        | Value           |
|----------------|-----------------|
| **Build Pack** | Nixpacks        |
| **Config file**| `nixpacks.toml` |
| **Start cmd**  | `php -S 0.0.0.0:$PORT` |
| **PHP Version**| 8.2             |

`nixpacks.toml` is in the repo root — Coolify reads it automatically on every deploy.

---

## 🚀 CI/CD — How Auto-Deploy Works

CI/CD is handled by Coolify's **GitHub webhook**. Every `git push` to `main` automatically triggers a redeploy.

### To enable it in Coolify (one-time setup):
1. Login to https://deploy.oryzen.app
2. Open the application → **Configuration** tab
3. Scroll to **Deployments** → enable **"Auto Deploy on Push"** (or "Watch Branch")
4. Copy the **Webhook URL** shown there
5. Go to GitHub repo → **Settings → Webhooks → Add webhook**
   - Payload URL: *(paste from Coolify)*
   - Content type: `application/json`
   - Events: ✅ Just the push event
   - Click **Add webhook**

After this, every `git push origin main` auto-deploys within ~60 seconds. ✅

---

## 📤 How to Push an Update (your daily workflow)

```bash
# 1. Make your code changes locally
# 2. Run locally to test: php -S localhost:8000

# 3. Commit and push
git add -A
git commit -m "describe what you changed"
git push origin main

# ✅ Coolify auto-deploys in ~60 seconds — done!
```

---

## 🗃️ Database Initialization (first-time only)

Run these SQL files **in order** via Coolify's MySQL terminal or phpMyAdmin:

```
1. database/schema.sql          ← creates all tables
2. database/seed_data.sql       ← inserts default categories & settings
3. database/rules_seed.sql      ← inserts all 44 detection rules
4. database/migration_v2.sql    ← adds activities table + AI model settings
```

> ⚠️ Only needed ONCE on first deployment. Regular code updates don't need this.

---

## 🔄 If You Need to Re-Deploy from Scratch

1. Login to Coolify → open the project
2. Delete the old application (optional)
3. Create **New Resource → Public Repository**
   - Repo: `https://github.com/Dibbotcf/Ai-Prompt-Gateway`
   - Branch: `main`
   - Build: `Nixpacks` (auto-detected from `nixpacks.toml`)
4. Set **Domain**: `aiprompt.astrozupi.com`
5. Add **Environment Variables** (see above)
6. Add **MySQL Database** service → update env vars with credentials
7. Click **Deploy**
8. Once running, initialize the database (see above)

---

## 📝 Application Pages

| Page           | URL path           |
|----------------|--------------------|
| Dashboard      | `/`                |
| Test Bench     | `/testbench.php`   |
| Rules Engine   | `/rules.php`       |
| Logs           | `/logs.php`        |
| Activities     | `/activities.php`  |
| Settings       | `/settings.php`    |
| Architecture   | `/architecture.php`|

---

## 🤖 AI Provider API Keys (set via /settings.php)

| Provider | Key Location | Free? |
|---|---|---|
| **Groq** | Settings page in app | ✅ Free (14,400 req/day) |
| **Google Gemini** | Settings page in app | ✅ Free tier |
| **OpenAI** | Settings page in app | 💳 Paid |
| **Simulated** | Built-in, no key needed | ✅ Always works |

---

*Last updated: June 2026 · Project: AI Prompt Security Gateway*
