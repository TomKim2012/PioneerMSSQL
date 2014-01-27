---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
/*Function SP_GetBalances

	Author: Douglas M Kaburu
	
	Date: Dec 9 2013

	Purpose: Returns either shares, savings or loan balances for any given customer

	Arguments: 
		1. @ClientID(varchar(10)): Client id of the customer in question
		2. @ReqType Tinyint: Request type(1. Shares, 2. Savings, 3. Loans)
		
	Return type: Numeric(12,2)

	NB: For Non-existent clients,or non-existent shares, savings or loans,the function will return 0
*/

CREATE Function SP_GetBalances
	(@ClientID Varchar(10),
	 @ReqType tinyint)
	Returns Numeric(18,2)
As
Begin
	Declare @Retval Numeric(18,2),@Returnzero Bit,@Accnr Varchar(10)
	Set @Retval = 0
	Set @Returnzero = 1
	
	If @ReqType = 1--Shares
		Begin
			Select @Accnr = Accnr From Client Where Clcode = @ClientID
			
			If @Accnr is Null
				Select @Accnr = Accnr From Cluster Where Cluscode = @ClientID
				
			If @Accnr Is Null
				Return 0
				
			Declare @Bought Numeric(18,2),@Sold Numeric(18,2)
			
			Select @Bought = Sum(Amount) From Shatrans Where Accnr = @Accnr And type = 'B'
			Select @Sold = Sum(-Amount) From Shatrans Where Accnr = @Accnr And type = 'S'
			Select @Retval = Isnull(@Bought,0)- Isnull(@Sold,0)
		End
		
	Else If @ReqType = 2--Savings
		Begin
			Select @Accnr = Accnr From Client Where Clcode = @ClientID
			
			If @Accnr is Null
				Select @Accnr = Accnr From Cluster Where Cluscode = @ClientID
				
			If @Accnr Is Null
				Return 0
				
			Select @Retval = Sum(Amount) From Savta Where @Accnr = Accnr
			
			Select @Retval = Isnull(@Retval,0)
		End
			
	Else If @ReqType = 3--Loans
		Begin
			If Not Exists(Select * From Loan Where Clcode + Cluscode = @ClientID)
				Return 0
		
			Declare @Dues Numeric(18,2),@Paid Numeric(18,2)
			Select @Dues = Sum(Mprinc + Mint + Mpen + Mcomm) From Memdues Where Lnr In
				(Select Lnr From Loan Where Clcode + Cluscode = @ClientID)
			Select @Paid = Sum(Mprinc + Mint + Mpen + Mcomm) From Memrepay Where Lnr In
				(Select Lnr From Loan Where Clcode + Cluscode = @ClientID)
				
			Return Isnull(@Dues,0) - Isnull(@Paid,0)
			
			Select 	@Retval = Isnull(@Dues,0) - Isnull(@Paid,0)	
		End
	Else 
		 Select @Retval = 0
		 
	Return @Retval	
End

Go
	
Create Trigger [dbo].[ClDel] On [dbo].[client] For Insert 
	As Insert Into MobileBanking..Client
	Select Clcode,accnr,clname,clsurname,clhusbname,cltitle,idcard,clbday,maill1,maill2,physadd,km,memdate,
	litt,english,langname,marstate,occupation,coacc,idcoacc,nrsig,graduated,'',kinname,kinaddress,exitdate,
	'',sex,acode,udf4,udf5,udf6,children,depend,educ,photo,signature,'',refno,phone,housing,statno,
	phone2,middlename,costcid,exitcatid,tuserid From Inserted

Go

Create  Trigger [dbo].[ClusDel] On [dbo].[cluster] For Insert 
	As Insert Into MobileBanking..Cluster
	Select Cluscode,name,accnr,physadd,km,memdate,maill1,maill2,nrsig,'',exitdate,'',acode,udf4,udf5,
	udf6,'',refno,statno,costcid,exitcatid,tuserid From Inserted
