# DDS Interactive Scenario Test Script

This document provides step-by-step instructions for testing the DDS application based on the interactive scenarios. Use this script to verify that the seeders are properly configured and the application is functioning as expected.

## Prerequisites

-   The DDS Laravel application is running at http://localhost:8000
-   The database has been seeded with the training scenario data:
    ```
    php artisan db:seed --class=TrainingUserSeeder
    php artisan db:seed --class=TrainingScenarioSeeder
    ```

## Test Scenario 1: New Employee Onboarding (Sofia)

### Step 1: Login as Sofia

1. Open your browser and navigate to http://localhost:8000
2. Enter the following credentials:
    - Username: sofia
    - Password: password
3. Click "Sign In"

**Expected Result**:

-   Sofia should be successfully logged in
-   The dashboard should display with menu items appropriate for the Accounting role
-   The user information in the top-right should show "Sofia Wijaya"

### Step 2: Review Invoices from TechSupply Inc.

1. Navigate to Invoices > All Invoices
2. Use the search/filter function to find invoices from supplier "TechSupply Inc."

**Expected Result**:

-   At least two invoices should be displayed:
    -   TS-2025-089 for Rp 56,250,000
    -   INV-2025-042 for Rp 120,500,000
-   Both invoices should be associated with project 000H

### Step 3: View Invoice Details

1. Click on invoice TS-2025-089 to view its details

**Expected Result**:

-   Invoice details should display correctly:
    -   Invoice Number: TS-2025-089
    -   Invoice Date: (Yesterday's date)
    -   Receive Date: (Today's date)
    -   Supplier: TechSupply Inc.
    -   PO Number: PO-2025-045
    -   Project: 000H
    -   Amount: Rp 56,250,000
    -   Type: Item
    -   Status: open

### Step 4: Check Additional Documents

1. While viewing the invoice details, check if there are any linked additional documents
2. Return to All Invoices
3. Search for additional documents with PO number "PO-2025-045"

**Expected Result**:

-   You should find at least one additional document (BAPP-2025-045-01)
-   The document type should be "BAPP"

## Test Scenario 2: Document Location Issues (Dewi)

### Step 1: Login as Dewi

1. Log out if currently logged in
2. Log in with the following credentials:
    - Username: dewi
    - Password: password
3. Click "Sign In"

**Expected Result**:

-   Dewi should be successfully logged in
-   The dashboard should display with menu items appropriate for the Accounting role

### Step 2: Check Document Locations

1. Navigate to Invoices > All Invoices
2. Use the search function to find invoices with numbers starting with "LOC-2025"

**Expected Result**:

-   You should see at least 5 invoices (LOC-2025-100 through LOC-2025-500)
-   The first two invoices (LOC-2025-100 and LOC-2025-200) should show "FIN" as their current location
-   The other three invoices (LOC-2025-300 through LOC-2025-500) should show "ACC" as their current location

### Step 3: Attempt to Create a Distribution

1. Navigate to Distributions > Create New
2. Select "Normal" as the distribution type
3. Select origin department "Accounting"
4. Select destination department "Logistics"
5. Try to select all five LOC-2025 invoices

**Expected Result**:

-   You should only be able to select the three invoices that are in the Accounting department
-   The two invoices in Finance should not be available for selection

## Test Scenario 3: Multi-Department Approval Process (Siti)

### Step 1: Login as Siti

1. Log out if currently logged in
2. Log in with the following credentials:
    - Username: siti
    - Password: password
3. Click "Sign In"

**Expected Result**:

-   Siti should be successfully logged in
-   The dashboard should display with menu items appropriate for the Project role

### Step 2: Check Government Project Invoices

1. Navigate to Invoices > All Invoices
2. Use the search/filter function to find invoices with project "GOV1" or flag "GOV-PROJECT"

**Expected Result**:

-   You should see 5 invoices (GOV-2025-100 through GOV-2025-500)
-   The total value of these invoices should be approximately Rp 2,500,000,000
-   Each invoice should have supporting documents

### Step 3: Check Supporting Documents

1. Click on one of the government project invoices to view its details
2. Check for linked additional documents

**Expected Result**:

-   Each invoice should have multiple supporting documents
-   Documents should include types like BAPP, Time Sheet, and Goods Issue
-   All documents should have the same project code (GOV1)

## Test Scenario 4: System Downtime Handling (Budi)

### Step 1: Login as Budi

1. Log out if currently logged in
2. Log in with the following credentials:
    - Username: budi
    - Password: password
3. Click "Sign In"

**Expected Result**:

-   Budi should be successfully logged in
-   The dashboard should display with menu items appropriate for the Logistics role

### Step 2: Check Urgent Documents

1. Navigate to Invoices > All Invoices
2. Use the search/filter function to find invoices with flag "URGENT"

**Expected Result**:

-   You should see 5 invoices (DT-2025-100 through DT-2025-500)
-   All invoices should show "LOG" as their current location
-   All invoices should have the "URGENT" flag

## Additional Tests

### Test Invoice Currency Format

1. Navigate to Invoices > All Invoices
2. Check the currency format for several invoices

**Expected Result**:

-   All monetary values should be displayed in Indonesian Rupiah (IDR)
-   The format should be consistent (e.g., "Rp 56.250.000" or similar format)

### Test User Permissions

1. Log in as different users (Sofia, Marcus, Jamal, etc.)
2. Check which menu items and features are available to each user

**Expected Result**:

-   Different roles should have different permissions
-   Admin users (like Riley) should have access to more features than regular users

## Troubleshooting

If the tests fail, check the following:

1. **Database Seeding**: Ensure the database was properly seeded with both TrainingUserSeeder and TrainingScenarioSeeder
2. **User Roles**: Verify that the users have the correct roles assigned
3. **Department Setup**: Check that all required departments exist in the system
4. **Data Integrity**: Verify that the relationships between invoices, additional documents, and other entities are correctly established

## Conclusion

This test script covers the basic functionality required for the interactive scenarios. By successfully completing these tests, you can confirm that the application is properly set up and ready for training sessions. If any issues are found, review the seeder files and make necessary adjustments.

For more comprehensive testing, additional scripts can be created to cover all scenarios in the DDS-Interactive-Scenarios.md document.
