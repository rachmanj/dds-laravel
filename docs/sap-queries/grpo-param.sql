-- GRPO (Penerimaan Barang) — versi ber-parameter untuk DDS.
-- Sumber: docs/sap-queries/grpo.sql (query asli tim Logistik).
-- Dua parameter posisi (?) = FromDate, ToDate (DocDate OPDN). Tanpa DECLARE/SET, tanpa FOR BROWSE.
SELECT
    [OPDN].DocDate 'GRPO Date',
    [OPDN].CreateDate 'GRPO Created Date',
    [OPDN].DocNum 'GRPO No',
    [PDN1].BaseRef 'PO No.',
    [OPOR].DocDate 'PO Date',
    [OPOR].CreateDate 'PO Created Date',
    CASE [OPOR].U_ARK_DelivStat WHEN 'Y' THEN 'Delivered' WHEN 'N' THEN 'Not Delivered' END [PO Delivery Status],
    [OPOR].U_MIS_DeliveryTime [PO Delivery Time],
    [PDN1].U_MIS_PRNoRow 'PR No.',
    [PDN1].ItemCode 'Item Code',
    [OITM].U_MIS_OEMPartNo 'OEM No.',
    [PDN1].Dscription 'Item Name',
    [PDN1].U_MIS_ConsRe1 'U Cons Re1',
    [PDN1].U_MIS_ConsRe2 'U Cons Re2',
    [PDN1].Quantity 'Quantity',
    [PDN1].U_MIS_UnitNo 'Unit No',
    [PDN1].Currency 'Currency',
    [PDN1].Price 'Price',
    [PDN1].Quantity * [PDN1].Price 'Total Price',
    [PDN1].unitMsr 'UoM',
    [PDN1].WhsCode 'Warehouse Code',
    [OWHS].WhsName 'Warehouse Name',
    [OPDN].U_MIS_Received 'Received By',
    [OPDN].U_MIS_Rectime 'Time',
    [PDN1].Project 'Project',
    [@MIS_CCDPT].Name 'Department',
    [OPDN].CardCode 'Vendor Code',
    [OCRD].CardName 'Vendor Name',
    [OPOR].Comments 'Comments'
FROM [PDN1]
INNER JOIN [OPDN] ON [OPDN].DocEntry = [PDN1].DocEntry
LEFT JOIN [OITM] ON [OITM].ItemCode = [PDN1].ItemCode
LEFT JOIN [@MIS_CCDPT] ON [@MIS_CCDPT].Code = [PDN1].OcrCode
LEFT JOIN [OWHS] ON [OWHS].WhsCode = [PDN1].WhsCode
LEFT JOIN [OPRJ] ON [OPRJ].PrjCode = [PDN1].Project
LEFT JOIN [OPOR] ON [PDN1].BaseRef = [OPOR].DocNum
LEFT JOIN [OCRD] ON [OCRD].CardCode = [OPDN].CardCode
WHERE [OPDN].DocDate >= ? AND [OPDN].DocDate <= ?
