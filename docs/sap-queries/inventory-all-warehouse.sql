SELECT
    T0.[U_MIS_ModeNo] 'Model no', 
    T0.[U_MIS_UnitNo] 'Unit No',
    T0.[ItemCode],
    T0.[ItemName],
    T0.[InvntryUom],
    T1.[OnHand] 'Instock',
    T1.[IsCommited] 'Committed',
    T1.[OnOrder] 'Ordered',
    (SELECT TOP 1 A0.Currency
     FROM (SELECT ItemCode, Currency, DocDate FROM PDN1
           UNION ALL
           SELECT ItemCode, Currency, DocDate FROM IGN1) A0
     WHERE A0.ItemCode = T0.[ItemCode]
     ORDER BY A0.DocDate DESC) As 'Currency',
    (SELECT TOP 1 A0.Price
     FROM (SELECT ItemCode, Price, DocDate FROM PDN1
           UNION ALL
           SELECT ItemCode, Price, DocDate FROM IGN1) A0
     WHERE A0.ItemCode = T0.[ItemCode]
     ORDER BY A0.DocDate DESC) As 'Last Purchase Price',
    T1.[OnHand] * 
    (SELECT TOP 1 A0.Price
     FROM (SELECT ItemCode, Price, DocDate FROM PDN1
           UNION ALL
           SELECT ItemCode, Price, DocDate FROM IGN1) A0
     WHERE A0.ItemCode = T0.[ItemCode]
     ORDER BY A0.DocDate DESC) As 'Total',
    T1.[WhsCode],
    T2.[WhsName],
    T2.[U_MIS_Project],
CASE T0.[frozenFor] WHEN 'N' THEN 'Active' WHEN 'Y' THEN 'Inactive' END as 'Status',
    (SELECT TOP 1 ORDR.DocNum
     FROM ORDR
     INNER JOIN RDR1 ON ORDR.DocEntry = RDR1.DocEntry
     WHERE RDR1.ItemCode = T0.ItemCode
     ORDER BY ORDR.DocDate DESC) as 'Last MR No',
    (SELECT TOP 1 ODLN.DocNum
     FROM ODLN
     INNER JOIN DLN1 ON ODLN.DocEntry = DLN1.DocEntry
     WHERE DLN1.ItemCode = T0.ItemCode
     ORDER BY ODLN.DocDate DESC) as 'Last MI No'
FROM OITM T0 
INNER JOIN OITW T1 ON T0.ItemCode = T1.ItemCode
INNER JOIN OWHS T2 ON T1.WhsCode = T2.WhsCode
WHERE T1.[OnHand] != ?
ORDER BY T1.[WhsCode];