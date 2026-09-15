-- Pemakaian — Delivery (ODLN + DLN1). Versi ber-parameter untuk DDS.
-- Sumber: cabang kedua UNION di docs/sap-queries/pemakaian.sql.
-- Dua parameter posisi (?) = FromDate, ToDate (a.DocDate).
-- Kolom WAJIB sama urut dan jumlahnya dengan usage-goods-issue-param.sql dan usage-ap-service-param.sql (31 kolom).
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
