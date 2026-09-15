/* SELECT FROM [dbo].[OIGE] a */
DECLARE @A AS DATE
DECLARE @B AS DATE
/* WHERE */
SET @A = /* a.DocDate 'FromDate' */ '[%0]'
SET @B = /* a.DocDate 'ToDate'  */ '[%1]'
select 
a.DocNum,
a.createDate,
a.DocDate,
'' 'WO No',
'' 'Subject',
'' 'Category',
b.VisOrder + 1  'Line',
c.U_MIS_IssuePName 'Issue Purpose',
d.U_MIS_CategoryName 'Job Category',
E.U_MIS_JobDesc 'Job Name',
a.U_MIS_UnitNo ' Unit No',
g.U_MIS_ModeNo 'Model No',
a.U_MIS_SerialNo 'Serial No',
a.U_MIS_HoursMeter 'Hours Meter',
b.ItemCode,
b.Dscription,
b.Quantity,
b.Stockprice,
b.Quantity * b.StockPrice Total,
A.U_MIS_Project,
f.WhsName,
a.U_MIS_NoBA,
'' [Order Type],
CASE 
WHEN U_MIS_CancelStat='Y' THEN 'Cancel'
END AS 'Status GI',
U_U_MIS_GRNo 'GR No',
NULL 'M Ret No',
NULL 'ItemCode',
NULL 'Dscription',
NULL 'Quantity',
a.Comments
from OIGE a
inner join IGE1 b on a.DocEntry =  b.DocEntry
left join [@MIS_ISSUEPURPOSE] c on a.U_MIS_IssuePurpose = c.U_MIS_IssuePCode
left join [@MIS_JOBCATEGORY] d on a.U_MIS_JobCategory = d.U_MIS_CategoryCode
left join [@MIS_JOBCODE] e on a.U_MIS_JobCode = e.Code
left join OWHS f on b.WhsCode = f.WhsCode
left join OITM g on a.U_MIS_UnitNo  = g.U_MIS_UnitNo

WHERE 
a.DocDate >= @A 
AND a.DocDate <= @B


UNION 
select 
a.DocNum,
a.createDate,
a.DocDate,
a.U_MIS_WoNo 'WO No',
J.Subject 'Subject',
CASE J.U_Mis_JobCtg
	WHEN 'A' THEN 'Schedule'
	WHEN 'B' THEN 'Unschedule'
	WHEN 'C' THEN 'Accident'
	WHEN 'D' THEN 'Addotional Job'
	WHEN 'E' THEN 'Unit Rental'
	END As 'Category',
b.VisOrder + 1  'Line',
c.U_MIS_IssuePName 'Issue Purpose',
d.U_MIS_CategoryName 'Job Category',
E.U_MIS_JobDesc 'Job Name',
a.U_MIS_UnitNo ' Unit No',
g.U_MIS_ModeNo 'Model No',
a.U_MIS_SerialNo 'Serial No',
a.U_MIS_HoursMeter 'Hours Meter',
b.ItemCode,
b.Dscription,
b.Quantity,
b.Stockprice,
b.Quantity * b.StockPrice Total,
A.U_MIS_Project,
f.WhsName,
a.U_MIS_NoBA,
a.U_MIS_OrderType,
''[Status Doc],
''[GR No],
h.DocNum 'M Ret No',
i.ItemCode,
i.Dscription,
i.Quantity,
a.Comments
from ODLN a
inner join DLN1 b on a.DocEntry =  b.DocEntry
Left Join RDN1 i on B.DocEntry=i.BaseEntry AND b.ItemCode=i.ItemCode
Left Join ORDN H on h.DocEntry=i.DocEntry 
Left Join OSCL	J on a.U_MIS_WoNo=J.DocNum
left join [@MIS_ISSUEPURPOSE] c on a.U_MIS_IssuePurpose = c.U_MIS_IssuePCode
left join [@MIS_JOBCATEGORY] d on a.U_MIS_JobCategory = d.U_MIS_CategoryCode
left join [@MIS_JOBCODE] e on a.U_MIS_JobCode = e.U_MIS_JobCode
left join OWHS f on b.WhsCode = f.WhsCode
left join OITM g on a.U_MIS_UnitNo  = g.U_MIS_UnitNo

WHERE 
a.DocDate >= @A 
AND a.DocDate <= @B


UNION
select 
a.DocNum,
a.createDate,
a.DocDate,
d.U_MIS_WoNo 'WO No',
e.Subject 'Subject',
CASE e.U_Mis_JobCtg
	WHEN 'A' THEN 'Schedule'
	WHEN 'B' THEN 'Unschedule'
	WHEN 'C' THEN 'Accident'
	WHEN 'D' THEN 'Addotional Job'
	WHEN 'E' THEN 'Unit Rental'
	END As 'Category',
b.VisOrder + 1  'Line',
c.U_MIS_IssuePName 'Issue Purpose',
NULL 'Job Category',
NULL 'Job Name',
b.U_MIS_UnitNo ' Unit No',
f.U_MIS_ModeNo 'Model No',
a.U_MIS_SerialNo 'Serial No',
a.U_MIS_HoursMeter 'Hours Meter',
b.ItemCode,
b.Dscription,
b.Quantity,
b.Stockprice,
b.LineTotal Total,
b.Project,
NULL,
NULL,
a.U_MIS_OrderType,
NULL,
NULL,
NULL,
NULL,
NULL,
NULL,
a.Comments
from OPCH a
inner join PCH1 b on a.DocEntry =b.DocEntry
left join ORDR d on b.U_MISMRNO =d.Docnum
left join OSCL e on d.u_mis_wono=e.docnum
left join [@MIS_ISSUEPURPOSE] c on a.U_MIS_IssuePurpose = c.U_MIS_IssuePCode
left join OITM f on b.U_MIS_UnitNo  = f.U_MIS_UnitNo
WHERE (b.ItemCode ='SV-CONSUMABLEPART' OR b.ItemCode ='SV-LABOUR' OR b.ItemCode ='SV-MILEAGE')AND
a.DocDate >= @A AND a.DocDate <= @B