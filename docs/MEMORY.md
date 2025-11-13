
- Implemented SAP ITO query sync on 2025-11-10: Uses Service Layer QueryService_ExecuteQuery for 'list_ito' with date params. Maps to AdditionalDocument mirroring ItoImport.php. Skips duplicates by ito_no only. Log to sap_logs for audit. Decision: Async job to handle large results without UI blocking; reuse import logic for consistency. (ID: 1234567)
- Implemented SAP A/P invoice creation on 2025-11-10: Async job with validation, payload mapping, retries, status updates, and logging. (ID: 7654321)
- Added reconciliation command on 2025-11-10: Hourly sync of SAP statuses to local invoices. (ID: 8765432)
