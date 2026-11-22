# Seller API Postman Collection

This directory also contains the Sanctum Lab collection. Preserve `README.md` for that collection.

This seller collection verifies the authenticated Laravel seller listing, lifecycle, and image management APIs.

## Importing

1. Open Postman.
2. Select Import.
3. Choose `Craigslist Upgrade - Seller API.postman_collection.json`.
4. Import it into your workspace.

## Variables

Set these collection variables before running:

- base_url: http://localhost:8000
- seller_email: craigslist.postman.2026@example.test
- seller_password: the local password for that account
- access_token: leave empty; populated by Login
- test_category_id: populated by Get Categories
- test_listing_id: populated by Create Disposable Draft Listing
- delete_listing_id: populated by Create Second Disposable Listing
- test_image_id: populated by Upload One Image
- secondary_image_id: populated by Upload Multiple Images
- third_image_id: populated by Upload Multiple Images when a second file is uploaded
- test_listing_title: populated automatically before listing creation

Do not store real production credentials in this collection.

## Running the collection

Run requests in the documented folder order:

1. Authentication
2. Category Preparation
3. Seller Listing CRUD
4. Listing Lifecycle
5. Image Management
6. Authorization
7. Cleanup

The login request must run before seller endpoints. The category request must run before listing creation.

## Image upload placeholders

The upload requests use blank `images[]` file fields. Before executing them:

1. Open the request in Postman.
2. Go to the Body tab.
3. Select form-data.
4. For each `images[]` field, choose an existing JPEG, PNG, or WebP file smaller than 5 MB.
5. Do not use SVG files because the API rejects them.

No image fixture files were created or modified by this collection.

## Safe cleanup

Cleanup requests use dynamically captured IDs only.

If you uploaded more than the captured image IDs, delete any remaining images manually before soft-deleting the disposable listing.

Never use seeded sample listing IDs 1-6 for destructive tests.
