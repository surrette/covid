cd $PSScriptRoot #Set the working directory to the  directory containing this script
#download .csv files
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$url = "https://raw.githubusercontent.com/nytimes/covid-19-data/master/us-counties.csv"
$output = "us-counties.csv"
Invoke-WebRequest -Uri $url -OutFile $output
$url = "https://raw.githubusercontent.com/nytimes/covid-19-data/master/us-states.csv"
$output = "us-states.csv"
Invoke-WebRequest -Uri $url -OutFile $output

#import .csv files into sqlite database using dot commands
cmd.exe /c "sqlite3.exe < commands.txt covid.txt"

#Surrette.NET
$ftp = [System.Net.FtpWebRequest]::Create("ftp://ftp.<YOUR WEBSITE>/covid.txt")
$ftp = [System.Net.FtpWebRequest]$ftp
$ftp.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
$ftp.Credentials = new-object System.Net.NetworkCredential("<FTP USERNAME>","<FTP PASSWORD>")
$ftp.UseBinary = $true
$ftp.UsePassive = $true
# read in the file to upload as a byte array
$content = [System.IO.File]::ReadAllBytes("$PSScriptRoot\covid.txt")
$ftp.ContentLength = $content.Length
# get the request stream, and write the bytes into it
$rs = $ftp.GetRequestStream()
$rs.Write($content, 0, $content.Length)
# be sure to clean up after ourselves
$rs.Close()
$rs.Dispose()