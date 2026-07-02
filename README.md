# CASPTI Mini ERP

CASPTI Mini ERP is a PHP native web application designed for legacy hosting with MySQL 4.1 compatibility.

## Project Structure

- `index.php`: front controller and route registration.
- `schema.sql`: complete DDL and initial seed data.
- `app/config/config.php`: database settings and fixed salt.
- `app/core`: routing, authentication and database helpers.
- `app/controllers`: JSON API endpoints.
- `app/models`: ERP data access and report logic.
- `app/views/layout.php`: main ERP shell.
- `public/css/app.css`: visual identity and responsive layout.
- `public/js/api.js`: Fetch API client.
- `public/js/app.js`: SPA behavior, forms, grids and reports.
- `scripts/execute_schema.php`: schema execution helper.

## Initial Access

- Configure administrator credentials directly in your deployment environment.
- Do not publish default credentials in documentation.

## Deployment Notes

1. Upload all files to the PHP hosting root.
2. Confirm `.htaccess` rewrite support is enabled.
3. Update `app/config/config.php` or environment variables if credentials change.
4. Execute `schema.sql` directly in the MySQL server or run `php scripts/execute_schema.php` from the hosting environment.
5. Access `index.php` in the browser.

## Local PHP (Project Portable)

- Local executable: `tools/php/php.exe`
- Check version: `tools/php/php.exe -v`
- Start local server: `powershell -ExecutionPolicy Bypass -File scripts/start_local_server.ps1`
- Local URL: `http://127.0.0.1:8080`

## ERP Coverage

- System administration and authentication.
- Global address book with DirPartyTable, LogisticsPostalAddress and LogisticsElectronicAddress.
- Customers and vendors with AX-style identifiers.
- Products, services and service codes.
- Service invoices, purchase orders and general journals.
- Financial reports with totals in the result footer.

## Multi-language and Dynamic Navigation

- User language is stored in `SysUserInfo.LanguageId`.
- Initial languages: `PT-BR` and `EN-US` in `SysLanguage`.
- UI labels and component names are stored in `SysLabelText`.
- Sidebar menu groups and submenu items are stored in `SysMenuGroup` and `SysMenuItem`.
- Incremental migration command for existing databases:
	- `tools/php/php.exe scripts/apply_i18n_navigation_migration.php`