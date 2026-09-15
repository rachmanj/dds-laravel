-- Pemakaian — AP Service (OPCH + PCH1). Versi ber-parameter untuk DDS.
-- Sumber: cabang ketiga UNION di docs/sap-queries/pemakaian.sql (item SV-CONSUMABLEPART, SV-LABOUR, SV-MILEAGE).
-- Dua parameter posisi (?) = FromDate, ToDate (a.DocDate).
-- Kolom WAJIB sama urut dan jumlahnya dengan usage-goods-issue-param.sql dan usage-delivery-param.sql (31 kolom).
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
