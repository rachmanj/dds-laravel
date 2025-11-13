-- ============================================
-- SQL Queries to Verify ITO Count (Nov 1-12, 2025)
-- Run these directly in SAP B1 SQL Management Studio
-- ============================================

-- ============================================
-- Query 1: Count using CreateDate (matches original list_ITO.sql logic)
-- This matches the original query's WHERE clause:
-- WHERE T0.[CreateDate] >= @A AND T0.[CreateDate] <= @B 
--   AND T2.[WhsCode] = T0.[Filler] 
--   AND T0.[U_MIS_TransferType] = 'OUT'
-- ============================================
SELECT COUNT(DISTINCT T0.[DocNum]) AS 'Total_ITO_Count_CreateDate'
FROM OWTR T0 
INNER JOIN WTR1 T1 ON T0.[DocEntry] = T1.[DocEntry]
INNER JOIN OITW T2 ON T1.[ItemCode] = T2.[ItemCode]
WHERE T0.[CreateDate] >= '2025-11-01 00:00:00' 
  AND T0.[CreateDate] <= '2025-11-12 23:59:59' 
  AND T2.[WhsCode] = T0.[Filler] 
  AND T0.[U_MIS_TransferType] = 'OUT'
ORDER BY T0.[DocNum] ASC

-- ============================================
-- Query 2: Count using DocDate (matches what we actually synced)
-- This matches our sync logic which used DocDate filter
-- ============================================
SELECT COUNT(DISTINCT T0.[DocNum]) AS 'Total_ITO_Count_DocDate'
FROM OWTR T0 
INNER JOIN WTR1 T1 ON T0.[DocEntry] = T1.[DocEntry]
INNER JOIN OITW T2 ON T1.[ItemCode] = T2.[ItemCode]
WHERE T0.[DocDate] >= '2025-11-01' 
  AND T0.[DocDate] <= '2025-11-12' 
  AND T2.[WhsCode] = T0.[Filler]
ORDER BY T0.[DocNum] ASC

-- ============================================
-- Query 3: Detailed list using CreateDate (original logic)
-- Shows all records that match the original query criteria
-- ============================================
SELECT DISTINCT
    T0.[DocNum] AS 'ito_no', 
    T0.[DocDate] AS 'ito_date', 
    T0.[CreateDate] AS 'ito_created_date',
    T11.[USER_CODE] AS 'ito_created_by',
    T11.[U_NAME],
    T0.[Filler] AS 'origin_whs', 
    T0.[U_MIS_ToWarehouse] AS 'destination_whs',
    T0.[U_MIS_TransferType] AS 'TransferType',
    T0.[Comments] AS 'ito_remarks',
    T4.[DocNum] AS 'grpo_no',
    T6.[DocNum] AS 'po_no'
FROM OWTR T0 
INNER JOIN WTR1 T1 ON T0.[DocEntry] = T1.[DocEntry]
INNER JOIN OITW T2 ON T1.[ItemCode] = T2.[ItemCode]
LEFT JOIN OPDN T4 ON T0.U_MIS_GRPONo = T4.DocNum
LEFT JOIN PDN1 T5 ON T4.DocEntry = T5.DocEntry
LEFT JOIN OPOR T6 ON T5.BaseRef = T6.DocNum
LEFT JOIN OUSR T11 ON T0.UserSign = T11.USERID
WHERE T0.[CreateDate] >= '2025-11-01 00:00:00' 
  AND T0.[CreateDate] <= '2025-11-12 23:59:59' 
  AND T2.[WhsCode] = T0.[Filler] 
  AND T0.[U_MIS_TransferType] = 'OUT'
ORDER BY T0.[DocNum] ASC

-- ============================================
-- Query 4: Detailed list using DocDate (what we synced)
-- Shows all records that match our sync criteria
-- ============================================
SELECT DISTINCT
    T0.[DocNum] AS 'ito_no', 
    T0.[DocDate] AS 'ito_date', 
    T0.[CreateDate] AS 'ito_created_date',
    T11.[USER_CODE] AS 'ito_created_by',
    T11.[U_NAME],
    T0.[Filler] AS 'origin_whs', 
    T0.[U_MIS_ToWarehouse] AS 'destination_whs',
    T0.[U_MIS_TransferType] AS 'TransferType',
    T0.[Comments] AS 'ito_remarks',
    T4.[DocNum] AS 'grpo_no',
    T6.[DocNum] AS 'po_no'
FROM OWTR T0 
INNER JOIN WTR1 T1 ON T0.[DocEntry] = T1.[DocEntry]
INNER JOIN OITW T2 ON T1.[ItemCode] = T2.[ItemCode]
LEFT JOIN OPDN T4 ON T0.U_MIS_GRPONo = T4.DocNum
LEFT JOIN PDN1 T5 ON T4.DocEntry = T5.DocEntry
LEFT JOIN OPOR T6 ON T5.BaseRef = T6.DocNum
LEFT JOIN OUSR T11 ON T0.UserSign = T11.USERID
WHERE T0.[DocDate] >= '2025-11-01' 
  AND T0.[DocDate] <= '2025-11-12' 
  AND T2.[WhsCode] = T0.[Filler]
ORDER BY T0.[DocNum] ASC

-- ============================================
-- Query 5: Count using CreateDate WITHOUT warehouse join condition
-- This matches what OData can do (no complex joins)
-- OData cannot replicate: T2.[WhsCode] = T0.[Filler]
-- ============================================
SELECT COUNT(DISTINCT T0.[DocNum]) AS 'Total_ITO_Count_CreateDate_NoJoin'
FROM OWTR T0 
WHERE T0.[CreateDate] >= '2025-11-01 00:00:00' 
  AND T0.[CreateDate] <= '2025-11-12 23:59:59'
  AND T0.[U_MIS_TransferType] = 'OUT'

-- ============================================
-- Query 6: Comparison - Show difference between CreateDate and DocDate
-- This helps identify why counts might differ
-- ============================================
SELECT 
    T0.[DocNum] AS 'ito_no',
    T0.[DocDate] AS 'DocDate',
    T0.[CreateDate] AS 'CreateDate',
    DATEDIFF(day, T0.[CreateDate], T0.[DocDate]) AS 'Days_Difference',
    T0.[U_MIS_TransferType] AS 'TransferType',
    T0.[Filler] AS 'origin_whs',
    T0.[U_MIS_ToWarehouse] AS 'destination_whs'
FROM OWTR T0 
INNER JOIN WTR1 T1 ON T0.[DocEntry] = T1.[DocEntry]
INNER JOIN OITW T2 ON T1.[ItemCode] = T2.[ItemCode]
WHERE (
    (T0.[CreateDate] >= '2025-11-01 00:00:00' AND T0.[CreateDate] <= '2025-11-12 23:59:59')
    OR 
    (T0.[DocDate] >= '2025-11-01' AND T0.[DocDate] <= '2025-11-12')
)
AND T2.[WhsCode] = T0.[Filler]
ORDER BY T0.[DocNum] ASC

