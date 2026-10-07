// pfSense bulk user import from CSV  (pfSense CE 2.7.x / Plus 23.x+)
// 1. Diagnostics > Command Prompt > Upload File  -> users.csv  (lands in /tmp)
// 2. Paste this into "Execute PHP Commands" and click Execute.
// CSV columns (first row = header): username,password,fullname,group
require_once("/etc/inc/auth.inc");
$file   = "/tmp/users.csv";
$users  = config_get_path('system/user', []);
$groups = config_get_path('system/group', []);
$uid    = (int) config_get_path('system/nextuid');
$names  = array_column($users, 'name');
$rows   = array_map('str_getcsv', file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
array_shift($rows);                    // skip header row
$new = [];
foreach ($rows as $r) {
  [$name, $pass, $full, $grp] = array_pad(array_map('trim', $r), 4, '');
  $ok = preg_match('/^[a-zA-Z0-9._-]{1,32}$/', $name) && $pass !== '';
  if (!$ok) { echo "SKIP invalid: $name\n"; continue; }
  if (in_array($name, $names)) { echo "SKIP exists:  $name\n"; continue; }
  $u = ['scope' => 'user', 'name' => $name, 'descr' => $full, 'uid' => $uid++,
        'expires' => '', 'authorizedkeys' => '', 'ipsecpsk' => ''];
  local_user_set_password($u, $pass);
  $found = ($grp === '');
  foreach ($groups as &$g) {
    if ($g['name'] === 'all' || $g['name'] === $grp) {
      if (!is_array($g['member'] ?? null)) { $g['member'] = []; }
      $g['member'][] = $u['uid'];
      $found = $found || $g['name'] === $grp;
    }
  }
  unset($g);
  if (!$found) { echo "WARN group '$grp' not found for $name\n"; }
  $users[] = $u; $names[] = $name; $new[] = $u;
  echo "ADDED: $name\n";
}
usort($users, fn($a, $b) => strcmp($a['name'], $b['name']));
config_set_path('system/user', $users);
config_set_path('system/group', $groups);
config_set_path('system/nextuid', $uid);
write_config("Imported " . count($new) . " users from CSV");
foreach ($new as $u) { local_user_set($u); }
unlink($file);                         // don't leave passwords in /tmp
echo "Done: " . count($new) . " users imported\n";
