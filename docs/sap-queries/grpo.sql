--Query Penerimaan Barang (GRPO)
/* SELECT FROM [dbo].[OPDN] T1*/
DECLARE @A AS DATETIME
DECLARE @B AS DATETIME
/* WHERE */
SET @A = /* T1.DocDate 'FromDate'  */ '[%0]'
SET @B = /* T1.DocDate 'ToDate' */ '[%1]'

SELECT	
[OPDN].DocDate 'GRPO Date', 
[OPDN].CreateDate 'GRPO Created Date',
[OPDN].DocNum 'GRPO No',
[PDN1].BaseRef 'PO No.',
[OPOR].DocDate'PO Date', 
[OPOR].CreateDate 'PO Created Date', 
CASE [OPOR].U_ARK_DelivStat WHEN 'Y' THEN 'Delivered' WHEN 'N' THEN 'Not Delivered' END [PO Delivery Status], 
[OPOR].U_MIS_DeliveryTime [PO Delivery Time], 
[PDN1].U_MIS_PRNoRow 'PR No.', 
[PDN1].ItemCode 'Item Code', 
[OITM].U_MIS_OEMPartNo 'OEM No.', 
[PDN1].Dscription 'Item Name',
[PDN1].U_MIS_ConsRe1,
[PDN1].U_MIS_ConsRe2,
[PDN1].Quantity,
[PDN1].U_MIS_UnitNo,
[PDN1].Currency,
[PDN1].Price,
[PDN1].Quantity*[PDN1].Price 'Total Price',
[PDN1].unitMsr 'UoM', 
[PDN1].WhsCode 'Warehouse Code',
[OWHS].WhsName 'Warehouse Name',
[OPDN].U_MIS_Received 'Received By',
[OPDN].U_MIS_Rectime 'Time',
[PDN1].Project 'Project', 
[@MIS_CCDPT].Name 'Department',
[OPOR].Comments
from [PDN1]
INNER JOIN [OPDN] ON [OPDN].DocEntry = [PDN1].DocEntry
LEFT JOIN [OITM] ON [OITM].ItemCode = [PDN1].ItemCode
LEFT JOIN [@MIS_CCDPT] ON [@MIS_CCDPT].Code = [PDN1].OcrCode
LEFT JOIN [OWHS] ON [OWHS].WhsCode = [PDN1].WhsCode
LEFT JOIN [OPRJ] ON [OPRJ].PrjCode = [PDN1].Project
LEFT JOIN [OPOR] ON [PDN1].BaseRef = [OPOR].DocNum
WHERE [OPDN].DocDate >= @A AND [OPDN].DocDate <= @B

FOR BROWSE