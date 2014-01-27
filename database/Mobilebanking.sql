
If Exists(Select * from Sysobjects where xtype = 'FN' And name = 'Fn_Padl')
	Drop Function Fn_Padl
Go

Create Function Fn_Padl
(@Mychar varchar(20),@Resultsize TinyInt)
Returns Varchar(20)
As 
Begin 
	Declare @Retval varchar(20)

	If Len(@Mychar) >=@Resultsize
		Set @Retval = @Mychar
	Else
		Set @Retval = REPLICATE('0',@Resultsize-Len(@Mychar)) + @Mychar

	return @Retval
End
	
Go

If Not Exists(Select * from Mergefinal..para Where varname = 'P_MOBILECASHACC')
	Insert Into Mergefinal..para(varname,vartype,varvalue,descr)
	Values('P_MOBILECASHACC','C','130014','Mobile App Cash Collection Account') 
Go

If Not Exists(Select * from Mergefinal..para Where varname = 'P_MOBFEEACC')
	Insert Into Mergefinal..para(varname,vartype,varvalue,descr)
	Values('P_MOBFEEACC','C','310022','Mobile App Mini-Statement Fee Account ') 
Go


CREATE  Procedure Sp_InsertMobiles
(@TerminalId Int, @UserId Int)
As
Begin
	Declare @ctcode varchar(10), @ntcode varchar(10),@Myyear varchar(2), @MycashAcc Varchar(10), @MyIncome varchar(10), @Mydate Datetime

	Select @Myyear = Right(Cast(YEAR(Getdate()) As Varchar(4)),2)

	Select @MycashAcc = varvalue From Mergefinal..para where varname = 'P_MOBILECASHACC'

	Select @MyIncome = varvalue From Mergefinal..para where varname = 'P_MOBFEEACC'

	Set @Mydate = Getdate()

	If Exists(Select * From SysobjectS Where xtype = 'U' And Name = 't_MobTrans')
		Truncate Table t_MobTrans
	Else
		Create Table t_MobTrans
		(Recid Int Identity(1,1), ClCode Varchar(10), Accnr Varchar(10), Mytcode Varchar(10), 
		MyAmount Numeric(12,2), Mydate datetime, Mytype Varchar(20), Glaccount varchar(6), Prevbalance Numeric(12,2),
		Trantype varchar(2),TranNarr varchar(30),Crdr Varchar(2))

	InSert Into t_MobTrans(ClCode, Accnr, Mytcode, MyAmount, Mydate, Mytype,Glaccount,Prevbalance,Trantype,TranNarr,Crdr)
	Select ClCode,'','',transaction_amount, cast(transaction_date As datetime) + cast(transaction_time As datetime) As MyTime, transaction_type,'',0,'','',''   
	From Transactions
	Where isRecorded = 0 And terminalId = @TerminalId And userId = @UserId

	Select @ctcode = varvalue From Mergefinal..Para Where varname = 'P_NEXTTCODE'

	If Isnumeric(@ctcode) = 1
		Set @ntcode = Cast(@ctcode As Int) 
	Else
		Begin
			Select @ctcode =Max(Right(Tcode,6)) From Mergefinal..genledg Where tcode like '%PD%' And len(Tday) = 10
			If ISNUMERIC(Isnull(@ctcode,0)) = 0
				set @ntcode = 0
			Else
				Set @ntcode = Cast(@ctcode As Int) 
		End	

	Update t_MobTrans Set t_MobTrans.Accnr = Mergefinal..client.accnr,
		Mytcode = @Myyear + 'PD' + Dbo.Fn_Padl(Cast((t_MobTrans.Recid + @ntcode) As Varchar(10)),6)
		From Mergefinal..client Where t_MobTrans.ClCode = Mergefinal..client.clcode

	Update t_MobTrans Set Glaccount = 
		Case Mytype
			When 'Deposit' then @MycashAcc
			When 'Mini-Statement' then @MyIncome
		End,Trantype = 
		Case Mytype
			When 'Deposit' then 'SD'
			When 'Mini-Statement' then 'SW'
		End, TranNarr = 
		Case Mytype
			When 'Deposit' then 'Savings Deposit'
			When 'Mini-Statement' then 'Mini-Statement Fees'
		End
		 

	Update t_MobTrans Set MyAmount = -MyAmount Where Mytype = 'Mini-Statement'
	Update t_mobtrans Set Crdr = 'DR' Where Mytype = 'SD'
	Update t_mobtrans Set Crdr = 'CR' Where Mytype = 'SW'

	Update t_MobTrans Set t_MobTrans.Mytype = Mergefinal..savacc.prodid From Mergefinal..savacc Where t_MobTrans.Accnr = Mergefinal..savacc.accnr
		And Mergefinal..savacc.closedate Is null

	Print 'The Rows Are ' + Cast(@@Rowcount as Varchar(10))
	
	If Exists(Select * From Sysobjects Where xtype = 'U' And Name = 't_Balances')
		Drop Table t_Balances

	Select Mergefinal..savta.accnr,Mergefinal..savta.prodid, Sum(Mergefinal..savta.Amount) As Mybalance
		Into t_Balances
		From Mergefinal..savta 
		Where Mergefinal..savta.accnr in(Select t_MobTrans.Accnr from t_MobTrans)
		Group By Mergefinal..savta.accnr,Mergefinal..savta.prodid

	Update t_MobTrans set t_MobTrans.Prevbalance = t_Balances.Mybalance 
		From t_Balances 
		Where t_MobTrans.Accnr = t_Balances.accnr And t_MobTrans.Mytype = t_Balances.prodid 

	Set @Mydate = Getdate()

	Select Mytcode,Accnr,Mytype,Trantype,MyAmount,Prevbalance,Trantype From t_MobTrans

	Insert Into Mergefinal..savta(tcode,accnr,prodid,tday,type,cash,cheqid,voucher,amount,balance,penalty,commission,stationery,uid,descr)
		Select Mytcode,Accnr,Mytype,@Mydate,Trantype,1,0,'MOBILE',MyAmount,Prevbalance,0,0,0,'Per',Trantype From t_MobTrans
		Where Len(t_MobTrans.Mytype) = 3

	If Exists(Select * From Sysobjects Where xtype = 'U' And Name = 't_products')
		Drop Table t_products

	Select prodid,savindacc Into t_products From Mergefinal..product
		Where prodid in(Select Mytype from t_MobTrans)

	Insert Into t_MobTrans(ClCode,Accnr,Mytcode,MyAmount, Mydate, Mytype, Glaccount, Prevbalance,Trantype,TranNarr,Crdr)
		Select ClCode,Accnr,Mytcode,MyAmount, Mydate, Mytype, '', Prevbalance,Trantype,TranNarr,''
		from t_MobTrans

	Update t_MobTrans Set t_MobTrans.Glaccount =  t_products.savindacc from t_products Where t_MobTrans.Mytype = t_products.prodid
		And t_MobTrans.Glaccount = ''

	Update t_MobTrans Set Crdr = 'DR' Where MyAmount < 0 And Crdr = ''

	
	Update t_MobTrans Set Crdr = 'CR' Where MyAmount > 0 And Crdr = ''

	Insert Into Mergefinal..genledg(Account,tcode,Tday,descriptio,Debit,credit,temp,uid,voucher,prodid,donorid,branchid,export,entrydate,lnr,accnr,trancode,costcid,cheqid)
		Select Glaccount,Mytcode,@Mydate,TranNarr, 
		case t_MobTrans.Crdr 
			when 'DR' then abs(t_MobTrans.MyAmount)
			when 'CR' then 0
		end,
		case t_MobTrans.Crdr 
			when 'CR' then abs(t_MobTrans.MyAmount)
			when 'DR' then 0
		end,1,'Per','MOBILE',Mytype,'00',Left(ClCode,2),1,Getdate(),'',Accnr,'S01','000',0
	From t_MobTrans 
	Where Len(t_MobTrans.Mytype) = 3
	Order By Accnr, Recid 

	Update Transactions Set isRecorded = 1  Where isRecorded = 0 and terminalId = @TerminalId And  userId = @UserId
End

Go

Create Trigger Tr_MobileUpdate On Allocation After update
As
Begin
	Declare @TerminalId int, @UserId int,@Deallocatedby Int, @Deallocationtime Datetime

	Select @Deallocationtime = deallocationDate, @Deallocatedby = DeallocatedBy,  @TerminalId = TerminalId, @UserId = allocatedTo From inserted

	If @Deallocationtime is not null And @Deallocatedby is not null 
		Exec Sp_InsertMobiles @TerminalId,@UserId
End

Go

