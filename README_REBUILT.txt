TIO PERFUME COLLECTION — REBUILT VERSION

SETUP
1. Put the Eurika folder inside C:\xampp\htdocs\
2. Start Apache and MySQL in XAMPP.
3. Import schema.sql in phpMyAdmin into the tio_perfume database.
4. Create the first admin at:
   http://localhost/Eurika/setup/createadmin.php
   (If your folder URL is different, use that folder path.)
5. Use ONE login page for customers and admins:
   http://localhost/Eurika/login.php

LOGIN FLOW
- Customer account -> index.php
- Admin account -> admin.php
- Login is required before the customer site or admin dashboard opens.
- One browser can keep a USER in one tab and an ADMIN in another tab.
- Each tab stores its own context in sessionStorage; no auth token is shown in the URL.
- User tab opening admin.php is redirected to index.php.
- Admin tab opening index.php is redirected to admin.php.
- Logging out removes only that tab's context.

ADMIN
- Store overview: orders, sales, customers, products, VIP members, messages, low stock.
- Recent orders with order/payment status editing.
- Add, edit, delete products.
- Upload product images.
- Add perfume sizes and update stock.

CUSTOMER
- Shop with size selection and Add to Bag.
- Cart quantity updates/removal.
- Checkout with GCash, Bank Transfer, or Cash on Delivery.
- GCash/Bank reference information is saved with the order.
- My Orders page shows order and payment status.

NOTES
- The old separate admin login/logout/stock files were removed.
- The old login/register pop-up modals were removed.
- Existing database data is not intentionally deleted. config.php includes small compatibility upgrades for older copies of the database.
