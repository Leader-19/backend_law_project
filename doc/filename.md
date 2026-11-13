# SPRITUP Application: Project Scope and User Flows

## 1. Project overview

SPRITUP is a web application for organizing and reading legal documents, managing paid access to document collections, and delivering learning activities. It includes an administrative workspace and authenticated user interfaces, with both web routes and a Sanctum-protected API.

The backend is built with Laravel 12 and PHP 8.2 or newer. The web interface uses Vue, TypeScript, and Inertia. User authentication, authorization, subscriptions, file storage, and payment processing are handled by Laravel and its connected services.

## 2. Project goals

- Let administrators organize documents into categories and control who can access them.
- Let registered users discover public content and purchase plans for plan-restricted collections.
- Support document upload, batch import, reading progress, and saved items.
- Provide quizzes, attempt history, leaderboards, and certificates.
- Give administrators tools to manage accounts, plans, payments, permissions, and backups.

## 3. User roles

### Visitor

- View the public landing, contact, and certificate-verification pages.
- Register or sign in when registration is enabled.
- View the available subscription plans.

### Registered user

- Sign in and use authenticated web or API features.
- Read public categories and their available documents.
- Access plan categories after an eligible paid subscription is active.
- Use the library, reading history, quizzes, certificates, and profile features allowed by the account.

### Administrator or staff member

- Access administrative functions according to assigned roles and permissions.
- Manage users, categories, documents, text content, plans, payments, quizzes, certificates, and activity logs.
- Assign category permissions and user subscriptions.
- Create category or database backups when granted the corresponding backup permissions.

## 4. Functional scope

### Accounts and security

- Registration, login, logout, password reset, email verification, and profile management.
- Optional Google sign-in and two-factor authentication.
- Role and permission checks for administrative operations.
- API authentication through Laravel Sanctum.
- Rate limits on sensitive routes and upload operations.

### Categories and access

Each category has one of two access types:

- **Public:** available to authenticated users.
- **Plan:** available when the user has an active eligible paid plan that includes the category, or an explicit category permission grants access.

Administrators can create and edit category trees, assign categories to plans, and grant category permissions to users or roles. Access to a child category does not automatically reveal a plan-restricted parent. Plan or explicit permissions can include descendants according to the access rules in the application.

### Documents and text content

- Create, view, edit, delete, and search documents.
- Organize documents and text content under categories.
- Save documents to a personal library and track reading progress.
- Apply subscription limits to content creation.
- Upload one document, multiple documents, or import a ZIP archive.

### Large and batch uploads

1. The client validates the selected file set and metadata.
2. Small uploads use the ordinary multipart endpoint. A batch whose combined size exceeds the client threshold is uploaded one file at a time to avoid one oversized request.
3. A large individual file is split into chunks of up to 5 MiB.
4. The client initializes an upload session, sends chunks with an index, and can query status or cancel the session.
5. The server checks session ownership and chunk count, stores chunks on disk, then streams them into the final file when completion is requested.
6. The server validates the category, permissions, subscription capacity, and declared file size before creating the document record.

The current chunked upload limit is 2 GiB per file. PHP, the web server, and any reverse proxy must permit the configured request sizes and storage requirements.

### Plans and payments

- Users can view plans and their category entitlements.
- A user can begin a Stripe checkout or submit a payment receipt where that payment workflow is enabled.
- Stripe webhook events or administrator review update the subscription state.
- Active eligible paid subscriptions unlock the associated plan categories.
- Expired or cancelled access follows the subscription rules; plan limits are enforced during creation.

### Learning features

- Users can browse and attempt active quizzes.
- Quiz settings can limit attempts, set time limits, and define passing scores.
- Users can view their results and attempt history.
- The application supports leaderboard and certificate features, including public certificate verification.

### Administration and operations

- User approval and account status management.
- Role, permission, and category access management.
- Subscription plan and payment review.
- Activity logs and operational health checks.
- Backups containing selected category data and files, with an optional database export.
- Contact form submission and administrative contact-message handling.

## 5. Main user flows

### A. New user access

1. Visitor registers or signs in using an enabled authentication method.
2. User verifies their email if required by the route.
3. User opens the authenticated dashboard.
4. User browses public categories and content.
5. If needed, user selects a plan and completes checkout or follows the receipt-payment workflow.
6. After payment confirmation, the application activates the subscription and exposes its plan categories.

### B. Category and document publishing

1. Administrator creates a category and chooses Public or Plan access.
2. For Plan access, administrator associates the category with one or more subscription plans.
3. Administrator or authorized content manager uploads documents or text content to the category.
4. The application checks permissions and applicable creation limits.
5. Registered users see the content only when its category is public or they have an applicable plan or explicit permission.

### C. Reading and learning

1. User opens an accessible document or text item.
2. The application records library or reading-progress activity where applicable.
3. User completes an active quiz associated with available content.
4. The application records the attempt and presents results.
5. Eligible completion can be reflected in certificates and leaderboard views.

### D. Administrative backup

1. An authorized administrator opens the backup page.
2. Administrator selects one or more root categories and whether to include the database.
3. The application expands the selection to include descendant categories.
4. It adds available files and category/document metadata to a ZIP archive, and optionally includes a database dump.
5. The completed archive is streamed to the administrator.

## 6. Interfaces

- **Web application:** Inertia pages for public, authenticated-user, and administrative workflows.
- **API:** JSON endpoints for authentication, categories, documents, billing, subscriptions, reading, quizzes, certificates, and related user features.
- **Webhooks:** Stripe and Telegram payment webhook endpoints update payment-related state after provider callbacks.

The routes define which operations are public, authenticated, role-protected, or permission-protected. Exact route names and middleware are maintained in routes/web.php and routes/api.php.

## 7. Project boundaries and dependencies

The application integrates with external services for payment processing, Google sign-in, email delivery, and optional Telegram payment notifications. Those providers require their own credentials and configuration; secrets belong in the server environment, not source control or browser code.

The application provides synchronous backup generation and file downloads. It does not currently describe a separate background-job dashboard or asynchronous backup delivery workflow.

Production behavior also depends on correct PHP upload limits, web-server and proxy timeouts, writable storage directories, a working public storage link, database backups, and scheduled Laravel tasks. Deployment settings are documented in docs/PRODUCTION_CONFIGURATION.md and payment setup is documented in docs/BILLING_SETUP.md.

## 8. Out of scope for this document

This document describes the current application flows and configured integrations. It is not a complete API reference, deployment runbook, database schema, legal compliance assessment, or specification for future features. Those should be documented separately when required.

## 9. Key implementation locations

- Web routes: routes/web.php
- API routes: routes/api.php
- User access and subscription rules: app/Models/User.php and app/Models/SubscriptionPlan.php
- Category and document records: app/Models/Category.php and app/Models/Document.php
- Large uploads: app/Http/Controllers/Api/ChunkedUploadController.php
- Document batch uploads: app/Http/Controllers/DocumentController.php
- Backup generation: app/Http/Controllers/BackupController.php
- Billing setup: docs/BILLING_SETUP.md
- Production setup: docs/PRODUCTION_CONFIGURATION.md
