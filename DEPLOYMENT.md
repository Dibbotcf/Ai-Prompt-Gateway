# AI Prompt Security Gateway — Deployment & Implementation Runbook

> **Purpose:** Complete record of how this project was built and deployed. Reference this file to re-deploy, update, or hand off the project.

---

## 1. Project Overview

**Application:** AI Prompt Security Gateway (PromptGuard)  
**Live URL:** https://aipromptgateway.astrozupi.com  
**GitHub:** https://github.com/Dibbotcf/Ai-Prompt-Gateway  
**Stack:** PHP 8 · MySQL · Vanilla JS · Chart.js 4 · IBM Plex Fonts · Font Awesome 6

---

## 2. Server Credentials

| Service | URL | Username | Password |
|---|---|---|---|
| **cPanel** | http://103.169.160.90:2082 / https://b201.serverdiana.com:2083 | `astrozup` | `8L+64GtCY7n;jk` |
| **MySQL DB** | via phpMyAdmin in cPanel | `astrozup_aipromptgu` | *(set in database.php)* |
| **DianaHost Client** | https://clients.dianahost.com.bd | `dibbodutta06@gmail.com` | `sRYYrN~fhhcg` |
| **GitHub** | https://github.com/Dibbotcf | `Dibbotcf` | `dibbo.tcfbd@06` |

### Database Config (`config/database.php`)
```php
host     = localhost
dbname   = astrozup_aipromptg
username = astrozup_aipromptgu
// password stored in config/database.php on server
```

---

## 3. File Upload Method (Used Throughout)

Files are uploaded via **cPanel File Manager API** (HTTPS multipart POST) using PowerShell.

```powershell
# Template — reuse this to upload any file
Add-Type @"
using System.Net; using System.Security.Cryptography.X509Certificates;
public class TC : ICertificatePolicy {
    public bool CheckValidationResult(ServicePoint sP, X509Certificate cert, WebRequest req, int certProb) { return true; }
}
"@
[System.Net.ServicePointManager]::CertificatePolicy = New-Object TC
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12

$cpUser = "astrozup"; $cpPass = "8L+64GtCY7n;jk"; $cpHost = "b201.serverdiana.com"
$authHeader = "Basic " + [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes("${cpUser}:${cpPass}"))
$base = "/home/astrozup/aipromptgateway.astrozupi.com"   # root on server

$bnd = [System.Guid]::NewGuid().ToString("N")
$fb  = [System.IO.File]::ReadAllBytes("D:\Antigravity Projects\AI prompt Gateway\<FILE>")
$hs  = "--$bnd`r`nContent-Disposition: form-data; name=`"dir`"`r`n`r`n$base`r`n" +
       "--$bnd`r`nContent-Disposition: form-data; name=`"overwrite`"`r`n`r`n1`r`n" +
       "--$bnd`r`nContent-Disposition: form-data; name=`"file-0`"; filename=`"<FILE>`"`r`nContent-Type: text/plain`r`n`r`n"
$fs  = "`r`n--$bnd--`r`n"
$ms  = New-Object System.IO.MemoryStream
foreach ($b in @([System.Text.Encoding]::UTF8.GetBytes($hs), $fb, [System.Text.Encoding]::UTF8.GetBytes($fs))) {
    $ms.Write($b, 0, $b.Length)
}
$wc = New-Object System.Net.WebClient
$wc.Headers.Add("Authorization", $authHeader)
$wc.Headers.Add("Content-Type", "multipart/form-data; boundary=$bnd")
$raw = $wc.UploadData("https://${cpHost}:2083/execute/Fileman/upload_files", "POST", $ms.ToArray())
Write-Host ([System.Text.Encoding]::UTF8.GetString($raw) | ConvertFrom-Json).data.uploads[0].reason
```

**Key points:**
- API endpoint: `https://b201.serverdiana.com:2083/execute/Fileman/upload_files`
- `overwrite = 1` to replace existing files
- The `dir` field must be the **absolute server path** (not relative)
- SSL cert validation is bypassed (shared hosting, self-signed cert)

---

## 4. Database Import Method

SQL is imported via **phpMyAdmin** in cPanel:

1. Go to `https://b201.serverdiana.com:2083` → login → phpMyAdmin
2. Select database `astrozup_aipromptg` from left panel
3. Click **SQL** tab → paste SQL → uncheck "Enable foreign key checks" → click **Go**

**Important:** If DELETE FROM fails due to foreign keys, run:
```sql
SET FOREIGN_KEY_CHECKS=0;
DELETE FROM rules;
SET FOREIGN_KEY_CHECKS=1;
```

---

## 5. CSS Cache Busting

Every time `style.css` is updated, increment the version number in `includes/header.php`:

```html
<link href="assets/css/style.css?v=3.0" rel="stylesheet">
```

Then upload both `style.css` and `header.php` to the server.

---

## 6. Git Workflow

```bash
cd "D:\Antigravity Projects\AI prompt Gateway"

git add -A
git commit -m "describe what changed"
git push origin main
```

**Remote:** https://github.com/Dibbotcf/Ai-Prompt-Gateway.git  
**Auth:** Username `Dibbotcf` / Password `dibbo.tcfbd@06` (use as token if prompted)

---

## 7. What Was Implemented (Completed Features)

### ✅ Hosting & Deployment
- Subdomain `aipromptgateway.astrozupi.com` created in cPanel
- PHP app deployed via File Manager API upload
- MySQL database `astrozup_aipromptg` initialized

### ✅ Database Schema (7 tables)
| Table | Purpose | Records |
|---|---|---|
| `attack_categories` | 5 threat taxonomy categories | 5 rows |
| `rules` | Detection rules with regex/keyword patterns | **44 rules** |
| `prompt_logs` | All analyzed prompts history | growing |
| `rule_matches` | Junction: which rule matched which log | growing |
| `sanitization_log` | PII/injection text transformations | growing |
| `settings` | API keys, model names, risk thresholds | system |
| `activities` | Test bench named sessions | growing |

### ✅ Detection Rules (44 rules across 5 categories)
- **Jailbreak Attempt (10):** DAN, ignore instructions, unrestricted mode, roleplay bypass, etc.
- **PII Exposure (10):** SSN, credit card, password, API key, database dump, etc.
- **Harmful Intent (10):** Weapon manufacturing, drug synthesis, malware, hacking, self-harm, etc.
- **System Override (8):** System prompt extraction, maintenance mode, config dump, etc.
- **Social Engineering (8):** False authority, urgency manipulation, researcher bypass, etc.

### ✅ UI Design System
- **Font:** IBM Plex Sans (UI) + IBM Plex Mono (code/data)
- **Theme:** Warm charcoal (`#0f0f0f`, `#171717`) — no blue tint
- **Accent:** Amber/gold `#f59e0b` — no neon, no gradients
- **Style:** Flat borders, sharp corners, enterprise security aesthetic

### ✅ Pages & Features
| Page | URL | What it does |
|---|---|---|
| Dashboard | `/` | KPI cards, threat trends chart, top rules, recent activity, risk gauge |
| Test Bench | `/testbench.php` | Create activity sessions, send prompts, get security verdicts, AI responses |
| Rules Engine | `/rules.php` | View/add/edit/delete/toggle detection rules |
| Logs & Analytics | `/logs.php` | Filter and search all prompt scan history |
| Activities | `/activities.php` | Test bench session history |
| Settings | `/settings.php` | API keys (Groq FREE, Gemini FREE, OpenAI paid), risk thresholds, model config |
| Architecture | `/architecture.php` | System docs, DB schema, API reference, risk scoring formula |

### ✅ AI Provider Integration
- **Groq** — 100% free (no credit card). 14,400 req/day. Model: `llama3-8b-8192`
- **Google Gemini** — Free tier 60 req/min. Model: `gemini-2.0-flash`
- **OpenAI GPT** — Paid (credit card required). Model: `gpt-3.5-turbo`
- **Simulated AI** — Built-in demo, always available, no key needed

To activate: Go to **Settings** → paste API key → Save → select provider in Test Bench

---

## 8. Known Limitations / Future Work

- [ ] **Authentication** — No login system yet. Anyone with the URL has full access.
- [ ] **Real-time alerts** — No webhook/email alerts when threats are detected.
- [ ] **Bulk rule import** — Must use phpMyAdmin SQL import for multiple rules.
- [ ] **Pattern testing** — No in-UI regex test tool before saving a rule.
- [ ] **Rate limiting** — No API rate limiting on `/api/analyze.php`.

---

## 9. Re-Deployment Checklist

If starting fresh or moving servers:

1. Upload all PHP files to new server root
2. Create MySQL database and user
3. Run `database/schema.sql` in phpMyAdmin  
4. Run `database/migration_v2.sql`
5. Run `database/seed_data.sql` (categories + settings)
6. Run `database/rules_seed.sql` (44 detection rules)
7. Update `config/database.php` with new DB credentials
8. Test at live URL

---

*Last updated: April 2026 · Project: AI Prompt Security Gateway*
