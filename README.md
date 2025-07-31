# CampaignConnect

CampaignConnect is a web-based email marketing platform built with core PHP, MySQL, and modern web technologies. It allows you to manage contacts, create and send email campaigns, design templates, and analyze campaign performance.

## Features

- **Authentication & User Management:** Secure login, role-based access control, user profile management.
- **Database & SMTP Configuration:** Connect external databases for contact data, manage multiple SMTP accounts for sending emails.
- **Contact Management:** Import/export contacts, create contact lists, segment contacts by type or status.
- **Campaign Management:** Multi-step wizard for creating campaigns, schedule or send instantly, preview personalized content.
- **Template Management:** Create and manage reusable email templates.
- **Analytics & Reporting:** Dashboard with metrics, campaign reports, open and click trends, basic analytics.
- **System Administration:** Audit logs, system health monitoring.

## Installation

1. **Database Setup:** Create a MySQL database and import the `db_schema.sql` file located in the project. This will create tables and insert a default superadmin user (`admin` / `admin123`).
2. **Configuration:** Update database credentials and other settings in `config/database.php` and `config/config.php`.
3. **Deployment:** Upload all project files to your web server (e.g., inside a subdirectory of your public web root). Ensure the web server has PHP and access to the MySQL database.
4. **Access:** Navigate to the application in your browser. Log in with the default admin credentials, then set up additional users as needed.

## Usage

Once logged in, you can:

- Configure external database connections and SMTP accounts under Settings.
- Import or create contacts and organize them into lists.
- Create campaigns using templates, assign recipient lists, and schedule or send.
- View reports to monitor email performance and engagement.

## Security

CampaignConnect implements security best practices such as:

- CSRF protection for all forms.
- Input validation and output sanitization to prevent SQL injection and XSS.
- Password hashing using SHA-256.
- Secure session management.

---

Contributions and feedback are welcome!
