# Real Homes

Real Homes is a real-estate website for browsing homes for sale and rent, contacting agents, and submitting property enquiries and listings. The interface is built with HTML, CSS, and browser JavaScript; PHP and MySQL handle account and form submissions.

## Website pages

Open the site from `HTML/HomePage.html`. The main pages are:

| Page | What it does |
| --- | --- |
| `HTML/HomePage.html` | Landing page, featured properties, and a property search form |
| `HTML/BuyPage.html` | Browse and filter sale listings, view property details, and enquire about a home |
| `HTML/RentPage.html` | Browse and filter rentals, view details, and submit rental enquiries or applications |
| `HTML/calculator.html` | Estimate bond repayments in the browser |
| `HTML/agent.html` | Browse agent profiles and contact them by email or phone |
| `HTML/contactUs.html` | Send a general message to the Real Homes team |
| `HTML/index.html` | Register or sign in to a client account |

The sticky header on each main page includes a **Sell Property** button. It opens a consultation form as an overlay without leaving the current page. The site uses shared styles in `HTML/css/style.css` and shared browser helpers in `HTML/js/main.js`. Search, filters, calculators, modals, and most page interactions run in the browser. Buy and rental listings added through the forms are saved to MySQL and cached in that browser's `localStorage`; the current site does not load listing cards from MySQL, so that local cache is not shared across browsers. Contact and selling requests are saved as messages. Rental applications and viewing requests currently show an on-page confirmation; property enquiries prepare an email in the visitor's email application.

## Requirements

- PHP 8.0 or later with the `mysqli` and `fileinfo` extensions enabled. `mbstring` is optional.
- MySQL 8.0 or later, with permission to create the databases and tables in `PHP/schema.sql`.
- A modern browser with JavaScript and `localStorage` enabled.
- Write permission for PHP to create and save files under `PHP/uploads/`.
- PHP upload settings that allow the site's image limits: `upload_max_filesize` of at least `5M`, `post_max_size` of at least `55M`, and `max_file_uploads` of at least `10`.

No Node.js packages, frontend build step, or package manager install is required.

## Set up and run locally

Run commands from the repository root.

1. Start MySQL and import the database setup. The schema creates the `realhomes`, `newsletter_db`, and `userid_db` databases and their required tables.

   On macOS/Linux:

   ```sh
   mysql -u root -p < PHP/schema.sql
   ```

   In Windows PowerShell:

   ```powershell
   Get-Content .\PHP\schema.sql | mysql -u root -p
   ```

2. Set the database connection environment variables in the same terminal that will run PHP. For example, in PowerShell:

   ```powershell
   $env:REALHOMES_DB_HOST = "127.0.0.1"
   $env:REALHOMES_DB_USER = "your_mysql_user"
   $env:REALHOMES_DB_PASSWORD = "your_local_mysql_password"
   ```

   The handlers default to `localhost`, user `root`, and an empty password if these are not set. Set credentials appropriate for your own machine instead of relying on those development defaults. Database names can also be overridden with `REALHOMES_DATABASE`, `CONTACT_DATABASE`, and `AUTH_DATABASE`; by default they are `realhomes`, `newsletter_db`, and `userid_db`, respectively.

3. Start PHP's built-in development server from the repository root:

   ```powershell
   php -S 127.0.0.1:8000
   ```

   The same command works in macOS/Linux shells.

4. Visit `http://127.0.0.1:8000/HTML/HomePage.html`. The sign-in and registration page is at `http://127.0.0.1:8000/HTML/index.html`.

Use a PHP-capable web server for backend forms; opening the HTML files directly with a `file://` URL will not execute PHP.

## Form and storage details

- Sale and rental listing forms post to `PHP/buy_add.php` and `PHP/rent_add.php`. Listing records and image paths are saved in MySQL. Rental listings accept up to 10 images; each image must be JPEG, PNG, or WebP and no larger than 5 MB.
- Uploaded images are placed in `PHP/uploads/sales/` or `PHP/uploads/rentals/`. PHP creates these directories when needed, but the web-server user must be allowed to write there.
- Contact and selling forms post to `PHP/contactUs.php` and save messages in `newsletter_db.contact_messages`.
- The selling consultation overlay is available from the header and the selling calls to action on the home page.
- Registration and sign-in use `HTML/auth.php`; passwords are stored as hashes in `userid_db.users`.
- The buy and rental pages keep newly submitted listing cards in browser `localStorage`, using the `realhomes_buylistings` and `realhomes_rentlistings` keys. Clearing browser storage removes those cached cards but does not delete database records.

## Common setup issues

- **Database connection error:** Confirm MySQL is running, the schema was imported, and the connection variables match a MySQL user with access to all three databases.
- **Image upload error:** Confirm `fileinfo` is enabled, the PHP size limits above are set, and the server can write to `PHP/uploads/`.
- **PHP code is shown or the form does not run:** Open the site through the PHP server URL, not as a local file.
