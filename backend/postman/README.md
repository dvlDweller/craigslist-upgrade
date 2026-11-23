# Postman Collection - Laravel Sanctum Lab

This directory contains the Postman collection for testing the instructor Laravel Sanctum token-authentication endpoints.

## Importing the collection

1. Open Postman.
2. Select Import.
3. Choose Craigslist Upgrade - Sanctum Lab.postman_collection.json.
4. Import the collection into your workspace.

## Setting collection variables

Open the collection, go to the Variables tab, and set:

- base_url: http://127.0.0.1:8000
- test_name: a display name, for example Postman Student
- test_email: a unique local test email, for example postman.student@example.test
- test_password: a local test password
- wrong_password: leave the provided default value
- access_token: leave empty; it is populated automatically by the login request

Do not place real credentials, production passwords, or real access tokens in this collection.

## Unique test email requirement

Registration creates a real user record in the craigslist_api database. Each run must use a unique test_email. Reusing an email will produce HTTP 422, which is expected for the duplicate-email test but not for the success test.

## Admin account

The instructor guide specifies an admin@example.com test account. That account is not recreated by this collection. Do not include its password in committed files or shared collections.

## Required request order

Run the requests in this order:

1. Register - Success
2. Register - Duplicate Email
3. Register - Invalid Input
4. Login - Success
5. Login - Invalid Credentials
6. Profile - No Token
7. Profile - Valid Token
8. Logout - Valid Token
9. Profile - Revoked Token

## Expected HTTP statuses

- Register - Success: 201 Created
- Register - Duplicate Email: 422 Unprocessable Content
- Register - Invalid Input: 422 Unprocessable Content
- Login - Success: 200 OK
- Login - Invalid Credentials: 401 Unauthorized
- Profile - No Token: 401 Unauthorized
- Profile - Valid Token: 200 OK
- Logout - Valid Token: 200 OK
- Profile - Revoked Token: 401 Unauthorized

## Token handling

The Login - Success request saves the returned access_token into the collection variable access_token. Subsequent protected requests use Bearer Token authorization with {{access_token}}.

The token value is not printed or logged by the collection tests.

## Why the revoked-token profile request must run last

The Profile - Revoked Token request must run after Logout - Valid Token because logout deletes the current access token. Running it earlier would revoke the token before the valid-token profile and logout requests.

## Credential safety

- Use only local test accounts.
- Do not commit real passwords, tokens, or production credentials.
- Keep the Admin password out of repository files.
- Clear access_token after testing if needed.
- Do not share exported environments containing secrets.
