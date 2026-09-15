-- Pemakaian — Goods Issue (OIGE + IGE1). Versi ber-parameter untuk DDS.
-- Sumber: cabang pertama UNION di docs/sap-queries/pemakaian.sql.
-- Dua parameter posisi (?) = FromDate, ToDate (a.DocDate).
-- Kolom WAJIB sama urut dan jumlahnya dengan usage-delivery-param.sql dan usage-ap-service-param.sql (31 kolom).
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
