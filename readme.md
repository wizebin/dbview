**DBViewer**

Configuration: edit the php/settings.json file to point to the sql server in question (postgres, mysql, or mssql only right now)

configuration skeleton:

	{
		"dbtype": "",
		"server": "",
		"database": "",
		"user": "",
		"pass": "",
		"apiroot": "",
		"credentialServerType": "",
		"credentialServer": "",
		"credentialDatabase": "",
		"credentialUsername": "",
		"credentialPassword": "",
		"credentialTable": "",
		"credentialUserColumn": "",
		"credentialPassColumn": "",
		"credentialAdminColumn": "",
		"userRO": "",
		"passRO": ""
	}
	
dbtype is the database type, it can be either mysql, pgsql, or mssql
server is the address of the db server
database is the root database for this configuration
user & pass are the db username and password for accessing the db
apiroot is the current webpage address, so if you are hosting blob.com and this page is on dbviewer/ the apiroot would be https://blob.com/dbviewer/

the credential subset is where you have the users who will use this page stored, this can be a completely separate db from your normal db

the credentialUserColumn is the username column to match up with the login username
the credentialPassColumn is the password column to match up with the login password
the credentialAdminColumn is the column that stores the account security level of that user, which is currently the method that determines admin access

