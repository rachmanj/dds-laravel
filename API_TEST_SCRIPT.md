# DDS Invoice API Test Script

## **Prerequisites**

-   DDS Laravel application running
-   Valid API key in `.env` file (`DDS_API_KEY`)
-   Test data in database (departments and invoices)

## **⚠️ Important Note for Windows Users**

**PowerShell Users**: PowerShell's `curl` is an alias for `Invoke-WebRequest` and has different syntax. Use the PowerShell commands provided below.

**Alternative Solutions**:

-   Use `Invoke-RestMethod` (recommended for JSON APIs)
-   Install real `curl` from https://curl.se/windows/
-   Use Windows Subsystem for Linux (WSL)
-   Use Git Bash or similar Unix-like terminal

## **Test 1: Health Check (No Authentication Required)**

**PowerShell (Windows):**

```powershell
Invoke-RestMethod -Uri "http://192.168.32.13/dds/api/health" -Headers @{"Accept" = "application/json"}
```

**Bash/Linux/macOS:**

```bash
curl -X GET "http://192.168.32.13/dds/api/health" \
  -H "Accept: application/json"
```

**Expected Response:**

```json
{
    "status": "healthy",
    "timestamp": "2025-01-21T10:30:00Z",
    "version": "1.0.0"
}
```

## **Test 2: Get Available Departments**

**PowerShell (Windows):**

```powershell
Invoke-RestMethod -Uri "http://192.168.32.13/dds/api/v1/departments" -Headers @{"X-API-Key" = "YOUR_DDS_API_KEY"; "Accept" = "application/json"}
```

**Bash/Linux/macOS:**

```bash
curl -X GET "http://your-domain.com/api/v1/departments" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

**Expected Response:**

```json
{
    "success": true,
    "data": {
        "departments": [
            {
                "id": 1,
                "name": "Accounting",
                "location_code": "000HACC",
                "akronim": "ACC"
            },
            {
                "id": 2,
                "name": "Finance",
                "location_code": "001HFIN",
                "akronim": "FIN"
            }
        ]
    },
    "meta": {
        "total_departments": 2,
        "requested_at": "2025-01-21T10:30:00Z"
    }
}
```

## **Test 3: Get Invoices by Department (Basic)**

```bash
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

**Expected Response:**

```json
{
    "success": true,
    "data": {
        "invoices": [
            {
                "invoice_number": "INV-001",
                "faktur_no": "FK-001",
                "invoice_date": "2025-01-15",
                "receive_date": "2025-01-20",
                "supplier_name": "Supplier ABC",
                "supplier_sap_code": "SUP001",
                "po_no": "PO-001",
                "receive_project": "PRJ001",
                "invoice_project": "PRJ001",
                "payment_project": "PRJ001",
                "currency": "IDR",
                "amount": 1000000.0,
                "invoice_type": "regular",
                "payment_date": "2025-02-15",
                "remarks": "Sample invoice",
                "status": "open",
                "sap_doc": "DOC001",
                "additional_documents": [
                    {
                        "document_no": "DOC-001",
                        "document_date": "2025-01-15",
                        "document_type": "supporting"
                    }
                ]
            }
        ]
    },
    "meta": {
        "department_location": "000HACC",
        "department_name": "Accounting",
        "total_invoices": 1,
        "requested_at": "2025-01-21T10:30:00Z",
        "filters_applied": []
    }
}
```

## **Test 4: Get All Invoices (No Pagination)**

```bash
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

## **Test 5: Get Invoices with Status Filter**

```bash
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices?status=open" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

## **Test 6: Get Invoices with Date Range Filter**

```bash
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices?date_from=2025-01-01&date_to=2025-01-31" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

## **Test 7: Invalid API Key (Security Test)**

```bash
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices" \
  -H "X-API-Key: INVALID_KEY" \
  -H "Accept: application/json"
```

**Expected Response:**

```json
{
    "success": false,
    "error": "Unauthorized",
    "message": "Invalid or missing API key"
}
```

## **Test 8: Invalid Location Code**

```bash
curl -X GET "http://your-domain.com/api/v1/departments/INVALID_CODE/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

**Expected Response:**

```json
{
    "success": false,
    "error": "Invalid location code",
    "message": "Department with the specified location code not found"
}
```

## **Test 9: Rate Limiting Test**

```bash
# Run this command multiple times quickly to test rate limiting
for i in {1..25}; do
  curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices" \
    -H "X-API-Key: YOUR_DDS_API_KEY" \
    -H "Accept: application/json"
  echo "Request $i completed"
  sleep 0.1
done
```

**Expected Response (after limit exceeded):**

```json
{
    "success": false,
    "error": "Rate limit exceeded",
    "message": "Minute rate limit exceeded. Please slow down your requests.",
    "retry_after": 60
}
```

## **Test 10: Missing API Key Header**

```bash
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices" \
  -H "Accept: application/json"
```

**Expected Response:**

```json
{
    "success": false,
    "error": "Unauthorized",
    "message": "Invalid or missing API key"
}
```

## **Test 11: Invalid Query Parameters**

```bash
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices?status=invalid_status" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

**Expected Response:**

```json
{
    "success": false,
    "error": "Validation failed",
    "message": "Invalid query parameters",
    "errors": {
        "status": ["The selected status is invalid."]
    }
}
```

## **Test 12: Invalid Date Format**

```bash
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices?date_from=invalid-date" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

## **Test 13: Check Rate Limit Headers**

```bash
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json" \
  -I
```

**Expected Headers:**

```
X-RateLimit-Limit-Hourly: 100
X-RateLimit-Remaining-Hourly: 99
X-RateLimit-Reset-Hourly: 1737457800
X-RateLimit-Limit-Minute: 20
X-RateLimit-Remaining-Minute: 19
X-RateLimit-Reset-Minute: 1737457860
```

## **Test 14: Test Different Department Location Codes**

```bash
# Test various location codes from your DepartmentSeeder
curl -X GET "http://your-domain.com/api/v1/departments/001HFIN/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"

curl -X GET "http://your-domain.com/api/v1/departments/017CWH/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"

curl -X GET "http://your-domain.com/api/v1/departments/000HIT/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

## **Test 15: Comprehensive Filtering**

```bash
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices?status=open&date_from=2025-01-01&date_to=2025-12-31" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

## **Test 16: Check Logs**

After running tests, check Laravel logs for API access:

```bash
tail -f storage/logs/laravel.log | grep "API:"
```

**Expected Log Entries:**

-   API access granted/denied
-   Rate limit exceeded warnings
-   Successful invoice retrievals
-   Error logs for invalid requests

## **Test 17: Performance Test**

```bash
# Test response time
time curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json" \
  -s > /dev/null
```

## **Test 18: Data Integrity Test**

```bash
# Verify that returned data matches database
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json" | jq '.data.invoices[0]'
```

## **Test 19: Additional Documents Test**

```bash
# Verify additional documents are properly nested
curl -X GET "http://your-domain.com/api/v1/departments/000HACC/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json" | jq '.data.invoices[0].additional_documents'
```

## **Test 20: Edge Cases**

```bash
# Test with empty location code (should return 400)
curl -X GET "http://your-domain.com/api/v1/departments//invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

**Expected Response:**

```json
{
    "success": false,
    "error": "Invalid location code",
    "message": "Location code cannot be empty"
}
```

### **Test 6: Verify Distribution Information in Response**

**Purpose**: Verify that the API now includes the latest distribution information for invoices where the destination department matches the requested department

**Request**:

```bash
curl -X GET "http://localhost:8000/api/v1/departments/000HACC/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

**PowerShell**:

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/v1/departments/000HACC/invoices" -Method GET -Headers @{"X-API-Key"="YOUR_DDS_API_KEY"; "Accept"="application/json"}
```

**Expected Response**: Should include `distribution` object (singular) with the latest distribution to the requested department:

```json
{
    "success": true,
    "data": {
        "invoices": [
            {
                "id": 1,
                "invoice_number": "1044/2025",
                "faktur_no": "FK-001",
                "invoice_date": "2025-08-07",
                "receive_date": "2025-08-20",
                "supplier_name": "Supplier ABC",
                "supplier_sap_code": "SUP001",
                "po_no": "PO-001",
                "receive_project": "PRJ001",
                "invoice_project": "PRJ001",
                "payment_project": "PRJ001",
                "currency": "IDR",
                "amount": 1000000.0,
                "invoice_type": "Regular",
                "payment_date": "2025-09-07",
                "remarks": "Sample invoice",
                "status": "open",
                "sap_doc": "DOC001",
                "additional_documents": [
                    {
                        "id": 1,
                        "document_no": "0118/IP/022",
                        "document_date": "2025-08-25",
                        "document_type": "Supporting"
                    }
                ],
                "distribution": {
                    "id": 1,
                    "distribution_number": "DIS-001",
                    "type": "Internal",
                    "origin_department": "Finance",
                    "destination_department": "Accounting",
                    "status": "sent",
                    "created_by": "John Doe",
                    "created_at": "2025-01-15 10:00:00",
                    "sender_verified_at": "2025-01-15 10:05:00",
                    "sent_at": "2025-01-15 10:10:00",
                    "received_at": null,
                    "receiver_verified_at": null,
                    "has_discrepancies": false,
                    "notes": "Regular monthly distribution"
                }
            }
        ]
    },
    "meta": {
        "department_location": "000HACC",
        "department_name": "Accounting",
        "total_invoices": 1,
        "requested_at": "2025-01-21T10:30:00Z",
        "filters_applied": {}
    }
}
```

**Verification Points**:

-   ✅ `distribution` object (singular) is present in each invoice
-   ✅ Only shows distributions where `destination_department` matches the requested department (000HACC = Accounting)
-   ✅ Shows the latest distribution (most recent `created_at`) to that department
-   ✅ Distribution fields include: `id`, `distribution_number`, `type`, `origin_department`, `destination_department`, `status`, `created_by`, `created_at`, `sender_verified_at`, `sent_at`, `received_at`, `receiver_verified_at`, `has_discrepancies`, `notes`
-   ✅ Date fields are formatted as "YYYY-MM-DD HH:MM:SS"
-   ✅ Department names are properly loaded from relationships
-   ✅ User names are properly loaded from relationships
-   ✅ If no distribution exists to the requested department, `distribution` will be `null`

## **Test Results Summary**

After running all tests, verify:

✅ **Authentication**: API key validation works correctly  
✅ **Rate Limiting**: Hourly and minute limits enforced  
✅ **Data Retrieval**: Invoices returned with correct structure  
✅ **Data Retrieval**: All invoices returned in single response  
✅ **Filtering**: Status and date filters function correctly  
✅ **Error Handling**: Proper error responses for invalid requests  
✅ **Empty Location Code**: Properly handled with 400 Bad Request  
✅ **Logging**: All API access properly logged  
✅ **Security**: Unauthorized access properly blocked  
✅ **Performance**: Response times are acceptable  
✅ **Data Integrity**: Returned data matches database

## **Troubleshooting**

If tests fail:

1. **Check API key**: Verify `DDS_API_KEY` in `.env` file
2. **Check middleware**: Ensure middleware is properly registered
3. **Check routes**: Verify API routes are accessible
4. **Check logs**: Review Laravel logs for error details
5. **Check database**: Ensure test data exists
6. **Check permissions**: Verify file permissions for new files

## **Next Steps**

After successful testing:

1. **Document API**: Create external API documentation
2. **Monitor Usage**: Set up API usage monitoring
3. **Performance Tuning**: Optimize database queries if needed
4. **Security Review**: Conduct security assessment
5. **User Training**: Train external developers on API usage
