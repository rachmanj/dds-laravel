# Training Scenario Seeders

This directory contains database seeders specifically designed to support the training scenarios in the Document Distribution System (DDS) training materials.

## Available Seeders

### TrainingScenarioSeeder

This seeder creates all the necessary data for the interactive training scenarios, including:

-   Suppliers with Indonesian addresses and contact information
-   Invoices with Rupiah (IDR) currency values
-   Additional documents linked to invoices
-   Data for the document location issues scenario
-   Data for the system downtime scenario
-   Data for the multi-department approval process scenario

### TrainingUserSeeder

Creates user accounts for all the characters mentioned in the training scenarios:

-   Sofia (Accounting) - New employee
-   Marcus (Accounting) - Senior accountant
-   Jamal (Logistics) - Logistics department
-   Taylor (Finance) - Finance analyst
-   Riley (Admin) - Administrator
-   Jordan (Finance) - Finance department
-   Alex (Finance) - Preparing for audit
-   Morgan (Project) - Project coordinator
-   Dewi (Accounting) - Document location scenario
-   Budi (Logistics) - System downtime scenario
-   Siti (Project) - Multi-department approval scenario
-   Rina (Legal) - Legal department user

All users have the password set to "password" for training purposes.

### DepartmentSeeder

Creates the departments needed for the training scenarios:

-   Accounting (ACC)
-   Finance (FIN)
-   Logistics (LOG)
-   Legal (LEG)
-   Project (PRJ)

### ProjectSeeder

Creates the projects referenced in the training scenarios:

-   000H - Main project
-   GOV1 - Government project
-   Additional sample projects

## How to Use

To seed the database with training scenario data, run:

```bash
php artisan db:seed --class=TrainingScenarioSeeder
php artisan db:seed --class=TrainingUserSeeder
```

Or to run all seeders including the training ones:

```bash
php artisan db:seed
```

## Currency

All monetary values in the training scenarios use Indonesian Rupiah (IDR) currency, with values appropriate for the Indonesian business context.

## Scenarios Supported

1. **New Employee Onboarding** - Sofia's scenario with TechSupply invoices
2. **Invoice Processing Challenge** - Marcus's scenario with project 000H invoices
3. **Distribution Emergency** - Jamal's urgent distribution scenario
4. **Month-End Reconciliation** - Taylor's reconciliation scenario
5. **Missing Document Investigation** - Riley's document status management scenario
6. **Payment Processing Rush** - Jordan's payment scenario with Rupiah values
7. **Audit Preparation** - Alex's audit scenario with high-value invoices
8. **Cross-Department Collaboration** - Morgan's project coordination scenario
9. **Document Location Issues** - Dewi's scenario with documents in different departments
10. **System Downtime Handling** - Budi's scenario with urgent distributions during maintenance
11. **Multi-Department Approval Process** - Siti's government project scenario with sequential approvals
