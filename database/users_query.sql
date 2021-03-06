USE [mobileBanking]
GO
/****** Object:  Table [dbo].[Users]    Script Date: 03/31/2014 18:20:01 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
CREATE TABLE [dbo].[Users](
	[userId] [int] IDENTITY(1,1) NOT NULL,
	[email] [varchar](100) NULL,
	[firstName] [varchar](255) NOT NULL,
	[isArchived] [bit] NULL,
	[lastName] [varchar](255) NOT NULL,
	[password] [varchar](255) NULL,
	[userName] [varchar](255) NOT NULL,
	[tuserid] [varchar](255) NULL,
 CONSTRAINT [PK__Users__CB9A1CFF286302EC] PRIMARY KEY CLUSTERED 
(
	[userId] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
SET ANSI_PADDING OFF
GO
SET IDENTITY_INSERT [dbo].[Users] ON
INSERT [dbo].[Users] ([userId], [email], [firstName], [isArchived], [lastName], [password], [userName], [tuserid]) VALUES (1, N'tosh0948@gmail.com', N'Tom', 0, N'Kimani', N'gitaru09', N'TomKim', N'TomKim')
INSERT [dbo].[Users] ([userId], [email], [firstName], [isArchived], [lastName], [password], [userName], [tuserid]) VALUES (2, N'mworia@empire.co.ke', N'James', 0, N'Mworia', N'mworia', N'james', N'James')
INSERT [dbo].[Users] ([userId], [email], [firstName], [isArchived], [lastName], [password], [userName], [tuserid]) VALUES (3, N'mworia@empire.co.ke', N'David', 0, N'Mworia', N'mworia', N'david', N'Mworia')
INSERT [dbo].[Users] ([userId], [email], [firstName], [isArchived], [lastName], [password], [userName], [tuserid]) VALUES (4, N'josephine@pioneerfsa.com', N'Josephine', 0, N'Mworia', N'mworia', N'josephine', N'Josephine')
INSERT [dbo].[Users] ([userId], [email], [firstName], [isArchived], [lastName], [password], [userName], [tuserid]) VALUES (9, N'info@sunbeam.com', N'Rose', 0, N'SunBeam', N'rose', N'rose', N'Rose')
INSERT [dbo].[Users] ([userId], [email], [firstName], [isArchived], [lastName], [password], [userName], [tuserid]) VALUES (10, N'daniel@pioneerfsa.com', N'Daniel', 0, N'Gumisirize', N'daniel', N'daniel', N'Daniel')
INSERT [dbo].[Users] ([userId], [email], [firstName], [isArchived], [lastName], [password], [userName], [tuserid]) VALUES (11, N'grace@pioneerfsa.com', N'Grace', 0, N'SunBeam', N'grace', N'grace', N'Grace')
INSERT [dbo].[Users] ([userId], [email], [firstName], [isArchived], [lastName], [password], [userName], [tuserid]) VALUES (12, N'joan@pioneerfsa.com', N'Joan', 0, N'Barma', N'joan', N'Joan', N'Joan')
INSERT [dbo].[Users] ([userId], [email], [firstName], [isArchived], [lastName], [password], [userName], [tuserid]) VALUES (13, N'eunice@pioneerfsa.com', N'Eunice', 0, N'Gikomba', N'Eunice', N'eunice', N'Eunice')
INSERT [dbo].[Users] ([userId], [email], [firstName], [isArchived], [lastName], [password], [userName], [tuserid]) VALUES (14, N'regina@pioneerfsa.com', N'Regina', 0, N'Muthurwa', N'regina', N'regina', N'Regina')
INSERT [dbo].[Users] ([userId], [email], [firstName], [isArchived], [lastName], [password], [userName], [tuserid]) VALUES (15, N'lucy@pioneerfsa.com', N'Lucy', 0, N'Gikomba', N'Lucy', N'lucy', N'Eunice')

SET IDENTITY_INSERT [dbo].[Users] OFF
/****** Object:  Default [DF_Users_isArchived]    Script Date: 03/31/2014 18:20:01 ******/
ALTER TABLE [dbo].[Users] ADD  CONSTRAINT [DF_Users_isArchived]  DEFAULT ((0)) FOR [isArchived]
GO
