-- Pemakaian (Gabungan 3 sumber) — versi ber-parameter untuk DDS.
-- Sumber: docs/sap-queries/pemakaian.sql (query asli tim Logistik).
-- WAJIB satu query UNION (bukan 3 query terpisah): UNION melakukan dedupe baris,
-- sehingga hasilnya sama dengan laporan asli (8.452 baris vs 8.427 di query asli untuk 16 Agu-15 Sep 2026).
-- Enam parameter posisi (?), urut: FromDate, ToDate (Goods Issue), FromDate, ToDate (Delivery), FromDate, ToDate (AP Service).

SELECT
    'Goods Issue' AS [Source],
    a.DocNum            [DocNum],
    a.createDate        [CreateDate],
    a.DocDate           [DocDate],
    ''                  [WO No],
    ''                  [Subject],
    ''                  [Category],
    b.VisOrder + 1      [Line],
    c.U_MIS_IssuePName  [Issue Purpose],
    d.U_MIS_CategoryName [Job Category],
    e.U_MIS_JobDesc     [Job Name],
    a.U_MIS_UnitNo      [Unit No],
    g.U_MIS_ModeNo      [Model No],
    a.U_MIS_SerialNo    [Serial No],
    a.U_MIS_HoursMeter  [Hours Meter],
    b.ItemCode          [ItemCode],
    b.Dscription        [Dscription],
    b.Quantity          [Quantity],
    b.Stockprice        [Stockprice],
    b.Quantity * b.StockPrice [Total],
    a.U_MIS_Project     [Project],
    f.WhsName           [WhsName],
    a.U_MIS_NoBA        [No BA],
    ''                  [Order Type],
    CASE WHEN a.U_MIS_CancelStat = 'Y' THEN 'Cancel' END [Status],
    a.U_U_MIS_GRNo      [GR No],
    NULL                [M Ret No],
    NULL                [Ret ItemCode],
    NULL                [Ret Dscription],
    NULL                [Ret Quantity],
    a.Comments          [Comments]
FROM OIGE a
INNER JOIN IGE1 b ON a.DocEntry = b.DocEntry
LEFT JOIN [@MIS_ISSUEPURPOSE] c ON a.U_MIS_IssuePurpose = c.U_MIS_IssuePCode
LEFT JOIN [@MIS_JOBCATEGORY] d ON a.U_MIS_JobCategory = d.U_MIS_CategoryCode
LEFT JOIN [@MIS_JOBCODE] e ON a.U_MIS_JobCode = e.Code
LEFT JOIN OWHS f ON b.WhsCode = f.WhsCode
LEFT JOIN OITM g ON a.U_MIS_UnitNo = g.U_MIS_UnitNo
WHERE a.DocDate >= ? AND a.DocDate <= ?
UNION
SELECT
    'Delivery' AS [Source],
    a.DocNum            [DocNum],
    a.createDate        [CreateDate],
    a.DocDate           [DocDate],
    a.U_MIS_WoNo        [WO No],
    j.Subject           [Subject],
    CASE j.U_Mis_JobCtg
        WHEN 'A' THEN 'Schedule'
        WHEN 'B' THEN 'Unschedule'
        WHEN 'C' THEN 'Accident'
        WHEN 'D' THEN 'Addotional Job'
        WHEN 'E' THEN 'Unit Rental'
    END                 [Category],
    b.VisOrder + 1      [Line],
    c.U_MIS_IssuePName  [Issue Purpose],
    d.U_MIS_CategoryName [Job Category],
    e.U_MIS_JobDesc     [Job Name],
    a.U_MIS_UnitNo      [Unit No],
    g.U_MIS_ModeNo      [Model No],
    a.U_MIS_SerialNo    [Serial No],
    a.U_MIS_HoursMeter  [Hours Meter],
    b.ItemCode          [ItemCode],
    b.Dscription        [Dscription],
    b.Quantity          [Quantity],
    b.Stockprice        [Stockprice],
    b.Quantity * b.StockPrice [Total],
    a.U_MIS_Project     [Project],
    f.WhsName           [WhsName],
    a.U_MIS_NoBA        [No BA],
    a.U_MIS_OrderType   [Order Type],
    ''                  [Status],
    ''                  [GR No],
    h.DocNum            [M Ret No],
    i.ItemCode          [Ret ItemCode],
    i.Dscription        [Ret Dscription],
    i.Quantity          [Ret Quantity],
    a.Comments          [Comments]
FROM ODLN a
INNER JOIN DLN1 b ON a.DocEntry = b.DocEntry
LEFT JOIN RDN1 i ON b.DocEntry = i.BaseEntry AND b.ItemCode = i.ItemCode
LEFT JOIN ORDN h ON h.DocEntry = i.DocEntry
LEFT JOIN OSCL j ON a.U_MIS_WoNo = j.DocNum
LEFT JOIN [@MIS_ISSUEPURPOSE] c ON a.U_MIS_IssuePurpose = c.U_MIS_IssuePCode
LEFT JOIN [@MIS_JOBCATEGORY] d ON a.U_MIS_JobCategory = d.U_MIS_CategoryCode
LEFT JOIN [@MIS_JOBCODE] e ON a.U_MIS_JobCode = e.U_MIS_JobCode
LEFT JOIN OWHS f ON b.WhsCode = f.WhsCode
LEFT JOIN OITM g ON a.U_MIS_UnitNo = g.U_MIS_UnitNo
WHERE a.DocDate >= ? AND a.DocDate <= ?
UNION
SELECT
    'AP Service' AS [Source],
    a.DocNum            [DocNum],
    a.createDate        [CreateDate],
    a.DocDate           [DocDate],
    d.U_MIS_WoNo        [WO No],
    e.Subject           [Subject],
    CASE e.U_Mis_JobCtg
        WHEN 'A' THEN 'Schedule'
        WHEN 'B' THEN 'Unschedule'
        WHEN 'C' THEN 'Accident'
        WHEN 'D' THEN 'Addotional Job'
        WHEN 'E' THEN 'Unit Rental'
    END                 [Category],
    b.VisOrder + 1      [Line],
    c.U_MIS_IssuePName  [Issue Purpose],
    NULL                [Job Category],
    NULL                [Job Name],
    b.U_MIS_UnitNo      [Unit No],
    f.U_MIS_ModeNo      [Model No],
    a.U_MIS_SerialNo    [Serial No],
    a.U_MIS_HoursMeter  [Hours Meter],
    b.ItemCode          [ItemCode],
    b.Dscription        [Dscription],
    b.Quantity          [Quantity],
    b.Stockprice        [Stockprice],
    b.LineTotal         [Total],
    b.Project           [Project],
    NULL                [WhsName],
    NULL                [No BA],
    a.U_MIS_OrderType   [Order Type],
    NULL                [Status],
    NULL                [GR No],
    NULL                [M Ret No],
    NULL                [Ret ItemCode],
    NULL                [Ret Dscription],
    NULL                [Ret Quantity],
    a.Comments          [Comments]
FROM OPCH a
INNER JOIN PCH1 b ON a.DocEntry = b.DocEntry
LEFT JOIN ORDR d ON b.U_MISMRNO = d.Docnum
LEFT JOIN OSCL e ON d.U_MIS_WoNo = e.DocNum
LEFT JOIN [@MIS_ISSUEPURPOSE] c ON a.U_MIS_IssuePurpose = c.U_MIS_IssuePCode
LEFT JOIN OITM f ON b.U_MIS_UnitNo = f.U_MIS_UnitNo
WHERE b.ItemCode IN ('SV-CONSUMABLEPART', 'SV-LABOUR', 'SV-MILEAGE')
  AND a.DocDate >= ? AND a.DocDate <= ?
