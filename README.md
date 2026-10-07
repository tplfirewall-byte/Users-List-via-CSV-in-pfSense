# Import Users into pfSense from a CSV File

Bulk-create local users in **pfSense** from a `.csv` file. You don't need any packages, SSH or console access. Everything is done from the webGUI.

pfSense has **no CSV import button** in *System › User Manager*. This repo gives you a short PHP script that you run on the built-in *Diagnostics › Command Prompt* page.

▶ **Video walkthrough:** _TPL Firewalls on YouTube (link coming soon)_

| | |
|---|---|
| **Works on** | pfSense CE 2.7.x, pfSense Plus 23.x and newer |
| **Needs** | Admin login to the webGUI |
| **Time** | About 1 minute, any number of users |

---

## Files

| File | What it is |
|---|---|
| [`import-users.php`](import-users.php) | The import script. Paste it into *Execute PHP Commands* |
| [`users.csv`](users.csv) | Example CSV. Replace the rows with your own users |

---

## 1. Back up your config first

**Diagnostics › Backup & Restore** → **Download configuration as XML**

If anything goes wrong, you can restore this file. You can also roll back from the *Config History* tab.

## 2. Prepare the CSV

The first row **must** be the header, because the script skips it:

```csv
username,password,fullname,group
rahul.sharma,R@hul#2026!,Rahul Sharma,staff
priya.verma,Pr1ya$Secure9,Priya Verma,staff
amit.kumar,Am!t_Kumar77,Amit Kumar,admins
```

| Column | Rules |
|---|---|
| `username` | Letters, numbers, `.` `_` `-`, max 32 characters. No spaces. |
| `password` | Required. Passwords containing commas must be in quotes: `"pa,ss"` |
| `fullname` | Optional. Shown as *Full name* in User Manager |
| `group` | Optional. Must **already exist** (*System › User Manager › Groups*). Leave it empty for no extra group |

- **Excel:** *File › Save As › CSV UTF-8 (Comma delimited)*
- **Name the file exactly `users.csv`**

## 3. Upload the CSV

**Diagnostics › Command Prompt** → *Upload File* → **Browse…** → choose `users.csv` → **Upload**

pfSense confirms: `Uploaded file to /tmp/users.csv.`

## 4. Run the import script

1. Open [`import-users.php`](import-users.php) and copy **all** of it ([raw link](https://raw.githubusercontent.com/tplfirewall-byte/Users-List-via-CSV-in-pfSense/main/import-users.php)).
2. On the same **Diagnostics › Command Prompt** page, paste it into the **Execute PHP Commands** box.
3. Click **Execute**.

You'll see one line per user:

```
ADDED: rahul.sharma
ADDED: priya.verma
ADDED: amit.kumar
Done: 3 users imported
```

## 5. Verify

- **System › User Manager › Users** should list every imported user with the right full name and group.
- Log out and sign in as one imported user to check the group's privileges.
- **Diagnostics › Backup & Restore › Config History** shows the import as a single change that you can revert.

---

## What the script does

1. Reads your existing users, groups and the next free user ID from the config.
2. Reads `/tmp/users.csv` and skips the header row.
3. For each row, it:
   - skips invalid usernames, empty passwords and users that **already exist**, so it's safe to run twice;
   - hashes the password with **bcrypt**, the same way the User Manager page does;
   - adds the user to the `all` group and to the group named in the CSV.
4. Saves everything in **one** config change and creates the accounts.
5. **Deletes `/tmp/users.csv`** so plain-text passwords aren't left on the firewall.

It only **adds** users. It never changes or deletes existing ones.

## Troubleshooting

| Output / problem | Fix |
|---|---|
| `SKIP exists: name` | That user is already there. Nothing was changed for it. |
| `SKIP invalid: name` | The username has a space or a disallowed character, or the password is empty. |
| `WARN group 'x' not found` | The user was created without that group. Create the group, then assign it in *User Manager*. |
| `file(/tmp/users.csv): Failed to open stream` | The file wasn't uploaded or has a different name. Upload it again as exactly `users.csv`. |
| User can't log in to the webGUI | Their group has no privileges. Edit the group and add *WebCfg* privileges. |
| First user is missing | Your CSV has no header row, so the first user was skipped as the header. Add the header line. |

**To undo an import:** go to *Diagnostics › Backup & Restore › Config History* and restore the entry just before *"Imported N users from CSV"*.

## Security notes

- The CSV holds plain-text passwords. Delete your local copy once the import is done.
- The script removes the uploaded copy from `/tmp` automatically.
- Ask users to change their password after their first login. They can do this at *System › User Password*.
- Only run scripts you've read. This one is short on purpose so you can check every line.

---

Stuck on a pfSense configuration? Open an [issue](https://github.com/tplfirewall-byte/Users-List-via-CSV-in-pfSense/issues) or comment on the video.

_Not affiliated with Netgate. pfSense is a trademark of Rubicon Communications, LLC (Netgate)._
