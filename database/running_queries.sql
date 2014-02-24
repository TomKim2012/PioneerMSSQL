/*delete from transactions where transaction_id='352'*/

/*--Update Transactions--
update transactions set isRecorded='1' where isRecorded='0'

update Allocation set deallocationDate= GETDATE(), deallocatedBy='10' where allocationId='83'
*/

/*==Latest Transactions  */
select * from transactions where isRecorded='0'

/*---Allocations ---*/
select * from Allocation

/*--- Terminals--*/
select * from Terminal where terminalId='15'