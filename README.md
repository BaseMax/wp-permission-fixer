# 🧩 wp-permission-fixer

[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.0-blue.svg)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-Compatible-success.svg)](https://wordpress.org/)
[![Maintainer](https://img.shields.io/badge/Maintainer-Seyyed%20Ali%20Mohammadiyeh%20(Max%20Base)-blueviolet.svg)](https://github.com/BaseMax)

A secure, CLI-based utility for **restoring and fixing WordPress file and directory permissions**.

This script automatically ensures your WordPress installation uses the correct permission structure:

- Directories → `755`  
- Files → `644`  
- `wp-config.php` → `600`

It includes safety checks, dry-run preview mode, ownership correction (`--chown`), and smart exclusion for `.git`, `node_modules`, and other non-core directories.

---

## ⚙️ Features

✅ Safely fixes all WordPress file & directory permissions  
✅ Compatible with **Linux**, **macOS**, and shared hosting  
✅ Optional dry-run mode (`--dry-run`)  
✅ Optional ownership correction (`--chown=user:group`)  
✅ Protects against symbolic link issues  
✅ Displays a full summary report and timing  
✅ Cross-platform safe (POSIX + Windows support)  

---

## 🧠 Usage

### 1️⃣ Upload or Clone
Place the script in the **root directory** of your WordPress installation:

```
/var/www/html/
├── wp-admin/
├── wp-content/
├── wp-includes/
├── wp-config.php
└── wp-permission-fixer.php
````

### 2️⃣ Run via CLI

#### Dry-run (preview only):

```bash
php wp-permission-fixer.php --dry-run
````

#### Apply permission fixes:

```bash
php wp-permission-fixer.php
```

#### Apply permissions and fix ownership (requires sudo):

```bash
sudo php wp-permission-fixer.php --chown=www-data:www-data
```

---

## 🔒 Recommended Permissions

| Type                  | Path                                       | Mode  |
| --------------------- | ------------------------------------------ | ----- |
| WordPress Directories | `/wp-content`, `/wp-admin`, `/wp-includes` | `755` |
| WordPress Files       | All `.php`, `.js`, `.css`, etc.            | `644` |
| Configuration         | `/wp-config.php`                           | `600` |
| .htaccess             | Root `.htaccess`                           | `644` |

---

## 🧰 Example Output

```
🔧 Starting WordPress permission fixer
📁 Root: /var/www/html
🧪 Dry-run mode enabled (no actual changes)

📊 Summary
   🗂️  Directories fixed: 24
   📄  Files fixed: 157
   🚫  Skipped: 12
   ⚠️  Errors: 0
   ⏱️  Time: 0.81s

✅ Permissions successfully fixed.
   Directories: 755 | Files: 644 | wp-config.php: 600
💡 Delete this script after use for better security.
```

---

## ⚡ Options

| Flag                 | Description                                                    |
| -------------------- | -------------------------------------------------------------- |
| `--dry-run`          | Preview what changes would be made, without modifying anything |
| `--chown=user:group` | Set ownership recursively (e.g., `--chown=www-data:www-data`)  |

---

## 🧩 Safety Notes

* Never run this script outside a WordPress root directory.
* Always back up your site before changing permissions.
* Delete this file after running it on production servers.
* Works best under CLI mode, not through a browser.

---

## 🧑‍💻 Author

**Seyyed Ali Mohammadiyeh (Max Base)**
📍 Senior Software Engineer, Researcher, and Craftsman
🌐 [GitHub: BaseMax](https://github.com/BaseMax)

---

## 📄 License

**MIT License**

---

## 🌟 Contributing

Pull requests are welcome!
If you find a bug or want to suggest an enhancement, feel free to [open an issue](https://github.com/BaseMax/wp-permission-fixer/issues).

---

**Made with 💻 & 🛠️ by [Max Base](https://github.com/BaseMax)**
“Secure WordPress, the right way.”
