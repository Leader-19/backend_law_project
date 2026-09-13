# Production configuration

Set these values in the **production server's** `backend_law_project/.env` before deploying. Do not copy the local development values.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://admin.spritup.site
FRONTEND_URL=https://spritup.site

FILESYSTEM_DISK=public

GOOGLE_CLIENT_ID=your-google-oauth-client-id
GOOGLE_CLIENT_SECRET=your-google-oauth-client-secret
GOOGLE_REDIRECT_URI=https://admin.spritup.site/auth/google/callback
```

In Google Cloud Console, add this exact value to **Authorized redirect URIs**:

```
https://admin.spritup.site/auth/google/callback
```

Deploy the backend with `./deploy.sh --force`. It applies the database migrations, refreshes Laravel's cached configuration, fixes write permissions, and creates the public storage link needed for receipt uploads.

After deployment, verify the API is live at:

```
https://admin.spritup.site/api/health
```

If that endpoint succeeds but payment still returns HTTP 500, inspect the server error immediately after reproducing it:

```bash
tail -n 100 storage/logs/laravel.log
```

Do not expose `APP_KEY`, database passwords, Google client secrets, Stripe secrets, or Telegram tokens in source control or browser logs.
