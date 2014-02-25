
--Run if from MergeFinals
 
Truncate Table MobileBanking..Client
Go
Insert Into MobileBanking..Client(Clcode,accnr,clname,clsurname,clhusbname,cltitle,idcard,clbday,maill1,maill2,physadd,km,memdate,litt,
	english,langname,marstate,occupation,coacc,idcoacc,nrsig,graduated,info,kinname,kinaddress,exitdate,exitreason,sex,
	acode,udf4,udf5,udf6,children,depend,educ,photo,signature,notes,refno,phone,housing,statno,phone2,middlename,costcid,exitcatid,tuserid)
Select Clcode,accnr,clname,clsurname,clhusbname,cltitle,idcard,clbday,maill1,maill2,physadd,km,memdate,litt,
	english,langname,marstate,occupation,coacc,idcoacc,nrsig,graduated,info,kinname,kinaddress,exitdate,exitreason,sex,
	acode,udf4,udf5,udf6,children,depend,educ,photo,signature,notes,refno,phone,housing,statno,phone2,middlename,costcid,exitcatid,tuserid 
	From Client
Go


