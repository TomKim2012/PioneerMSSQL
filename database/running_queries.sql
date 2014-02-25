/*delete from transactions where transaction_id='352'*/

/*--Update Transactions--
update transactions set isRecorded='1' where isRecorded='0'

update Allocation set deallocationDate= GETDATE(), deallocatedBy='10' where allocationId='88'
*/

/*==Latest Transactions  */
select * from transactions where clCode='pb/02495' and transaction_date='2014-02-24'

select * from Client where clcode='PB/02564'

update Client set phone='0721797962' where clcode='PA/00197' 

/*---Allocations ---*/
select * from Allocation

/*--- Terminals--*/
select * from Terminal where terminalId='15'