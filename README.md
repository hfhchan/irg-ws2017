IRG WS2017 Review Tool
======================

## Installation
1. Download Repository using git pull.
2. Download data files included in the releases page.
3. Unzip the data files directly into /data/.
4. Create /comments/ folder with read-write access.
5. Chmod /data/review/ with read-write access.
6. Rename `login.sqlite3-example` to `login.sqlite3`.
7. Replace `current-database.sqlite3-example` to `current-database.sqlite3` if necessary.
8. Set up Apache with PHP support, and visit /app/ in the browser.
9. Set up user accounts in /app/admin.php (see notes).

## Paths
- Main app: /app/
- Login/Admin page: /app/admin.php
- Chart generator: /app/chart.php
- Consolidated comments list generator: /app/list.php

## Notes
- To set up the first admin user, comment out /app/admin.php lines 56 - 58, 145 and 149.
- The admin user privilege is hardcoded to the user IDs set in /app/user_chk.php line 37.

## Submission of data for consolidation
- Zip the files in /data/review/current-database.sqlite3 and also the files in the /comments/ folder.