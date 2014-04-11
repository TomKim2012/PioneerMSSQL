
Alter  Procedure Sp_InsertMobiles
(@TerminalId Int, @UserId Int)
As
Begin
	Declare @ctcode varchar(10), @ntcode varchar(10),@Myyear varchar(2), @MycashAcc Varchar(10), @MyIncome varchar(10), @Mydate Datetime

	Select @Myyear = Right(Cast(YEAR(Getdate()) As Varchar(4)),2)

	Select @MycashAcc = varvalue From MergeFinals..para where varname = 'P_MOBILECASHACC'

	Select @MyIncome = varvalue From MergeFinals..para where varname = 'P_MOBFEEACC'

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

	Select @ctcode = varvalue From MergeFinals..Para Where varname = 'P_NEXTTCODE'

	If Isnumeric(@ctcode) = 1
		Set @ntcode = Cast(@ctcode As Int) 
	Else
		Begin
			Select @ctcode =Max(Right(Tcode,6)) From MergeFinals..genledg Where tcode like '%PD%' And len(Tday) = 10
			If ISNUMERIC(Isnull(@ctcode,0)) = 0
				set @ntcode = 0
			Else
				Set @ntcode = Cast(@ctcode As Int) 
		End	

	Update t_MobTrans Set t_MobTrans.Accnr = MergeFinals..client.accnr,
		Mytcode = @Myyear + 'PD' + Dbo.Fn_Padl(Cast((t_MobTrans.Recid + @ntcode) As Varchar(10)),6)
		From MergeFinals..client Where t_MobTrans.ClCode = MergeFinals..client.clcode

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
	Update t_mobtrans Set Crdr = 'DR' Where Trantype = 'SD'
	Update t_mobtrans Set Crdr = 'CR' Where Trantype = 'SW'

	Update t_MobTrans Set t_MobTrans.Mytype = MergeFinals..savacc.prodid From MergeFinals..savacc Where t_MobTrans.Accnr = MergeFinals..savacc.accnr
		And MergeFinals..savacc.closedate Is null

	Declare @InvalidTrans Int

	Select @InvalidTrans = (Select Count(*) From t_MobTrans Where Len(Mytype) <> 3)

	If Isnull(@InvalidTrans,0) <> 0
	Begin
		Print 'There are ' + Cast(@InvalidTrans as varchar(10)) + ' invalid transactions'
		Select * From t_MobTrans Where Len(Mytype) <> 3
		Delete From t_MobTrans Where Len(Mytype) <> 3
	End

	Print 'The Rows Are ' + Cast(@@Rowcount as Varchar(10))
	
	If Exists(Select * From Sysobjects Where xtype = 'U' And Name = 't_Balances')
		Drop Table t_Balances

	Create Table t_Balances(Recid Int Identity(1,1),Accnr varchar(10),Prodid varchar(4),Mybalance Numeric(12,2))
	
	Insert Into t_Balances	
	Select MergeFinals..savta.accnr,MergeFinals..savta.prodid, Sum(MergeFinals..savta.Amount) 	
		From MergeFinals..savta 
		Where MergeFinals..savta.accnr in(Select t_MobTrans.Accnr from t_MobTrans)
		Group By MergeFinals..savta.accnr,MergeFinals..savta.prodid

	Declare @MutipleTrans Table(m_Accnr Varchar(10), m_Prodid Varchar(4), m_Count Numeric(12,2)) 

	Insert Into @MutipleTrans
	Select accnr,Mytype, Count(*)  
		From t_MobTrans
		Group By accnr,Mytype
		Having Count(*) > 1
	
	Declare @My_Recid Int ,@My_Accnr Varchar(10),@My_Prodid varchar(4),@LastBalance Numeric(20),@OldAc varchar(10), @OldProd varchar(4)
	
	Set @OldProd = ''
	Set @OldAc = ''

	Declare cursor1 Cursor For
		Select Recid,Accnr,Mytype From t_MobTrans Where Accnr + Mytype In(Select m_Accnr +  m_Prodid From @MutipleTrans)
	Open Cursor1
	Fetch Next From Cursor1 Into @My_Recid,@My_Accnr,@My_Prodid
	While @@FETCH_STATUS = 0
	Begin
		If @OldAc <>  @My_Accnr Or @OldProd <> @My_Prodid
			Begin
				Select Top 1 @LastBalance =  Mybalance from t_Balances Where Accnr =@My_Accnr And Prodid = @My_Prodid

				Select @LastBalance = @LastBalance + t_MobTrans.MyAmount 
					From t_MobTrans Where Recid = @My_Recid

				Update t_MobTrans set Prevbalance = @LastBalance Where Recid = @My_Recid
			End
		Else
			Begin
				Select @LastBalance = @LastBalance + t_MobTrans.MyAmount 
					From t_MobTrans Where Recid = @My_Recid

				Update t_MobTrans set Prevbalance = @LastBalance Where Recid = @My_Recid
			End

			Set @OldAc = @My_Accnr
			Set @OldProd = @My_Prodid

			Print 'Recid ' + Cast(@My_Recid as Varchar(10)) + '  ' + Cast(@LastBalance As Varchar(10))

		Fetch Next From Cursor1 Into @My_Recid,@My_Accnr,@My_Prodid
	End
	Close Cursor1
	Deallocate Cursor1

	Update t_MobTrans set t_MobTrans.Prevbalance = t_Balances.Mybalance 
		From t_Balances 
		Where t_MobTrans.Accnr = t_Balances.accnr And t_MobTrans.Mytype = t_Balances.prodid 
		And t_MobTrans.Accnr + t_MobTrans.Mytype Not In(Select m_Accnr +  m_Prodid From @MutipleTrans)

	Update t_MobTrans set t_MobTrans.Prevbalance = t_MobTrans.Prevbalance-t_MobTrans.MyAmount 
		From t_Balances 
		Where t_MobTrans.Accnr + t_MobTrans.Mytype In(Select m_Accnr +  m_Prodid From @MutipleTrans)
	
	Set @Mydate = Getdate()

	Select Mytcode,Accnr,Mytype,Trantype,MyAmount,Prevbalance,Trantype From t_MobTrans

	Insert Into MergeFinals..savta(tcode,accnr,prodid,tday,type,cash,cheqid,voucher,amount,balance,penalty,commission,stationery,uid,descr)
		Select Mytcode,Accnr,Mytype,@Mydate,Trantype,1,0,'MOBILE',MyAmount,Prevbalance + MyAmount,0,0,0,'Per',Trantype From t_MobTrans
		Where Len(t_MobTrans.Mytype) = 3

	If Exists(Select * From Sysobjects Where xtype = 'U' And Name = 't_products')
		Drop Table t_products

	Select prodid,savindacc Into t_products From MergeFinals..product
		Where prodid in(Select Mytype from t_MobTrans)

	Insert Into t_MobTrans(ClCode,Accnr,Mytcode,MyAmount, Mydate, Mytype, Glaccount, Prevbalance,Trantype,TranNarr,Crdr)
		Select ClCode,Accnr,Mytcode,MyAmount, Mydate, Mytype, '', Prevbalance,Trantype,TranNarr,''
		from t_MobTrans

	Update t_MobTrans Set t_MobTrans.Glaccount =  t_products.savindacc from t_products Where t_MobTrans.Mytype = t_products.prodid
		And t_MobTrans.Glaccount = ''

	Update t_MobTrans Set Crdr = 'DR' Where MyAmount < 0 And Crdr = ''

	
	Update t_MobTrans Set Crdr = 'CR' Where MyAmount > 0 And Crdr = ''

	Insert Into MergeFinals..genledg(Account,tcode,Tday,descriptio,Debit,credit,temp,uid,voucher,prodid,donorid,branchid,export,entrydate,lnr,accnr,trancode,costcid,cheqid)
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
	Order By Mytcode, Recid 

	Update Transactions Set isRecorded = 1  Where isRecorded = 0 and terminalId = @TerminalId And  userId = @UserId
End

