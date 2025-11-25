# Configurações do FTP
$ftpHost = "ftp.seusite.com"
$ftpUser = "seu_usuario"
$ftpPass = "sua_senha"

# Pastas local e remota
$localDir = "C:\Projetos\SIG\public_html"
$remoteDir = "/public_html"

# Função para enviar arquivos modificados
function Upload-FtpFile {
    param(
        [string]$localFile,
        [string]$remoteFile
    )

    $uri = "ftp://$ftpHost$remoteFile"
    $ftp = [System.Net.FtpWebRequest]::Create($uri)
    $ftp.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $ftp.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile

    $content = [System.IO.File]::ReadAllBytes($localFile)
    $ftpStream = $ftp.GetRequestStream()
    $ftpStream.Write($content, 0, $content.Length)
    $ftpStream.Close()
}

# Percorre os arquivos locais e envia apenas os alterados
Get-ChildItem -Recurse $localDir | ForEach-Object {
    if (-not $_.PSIsContainer) {
        $relativePath = $_.FullName.Substring($localDir.Length)
        $remoteFile = "$remoteDir$relativePath".Replace("\","/")
        Write-Host "Enviando: $relativePath"
        Upload-FtpFile $_.FullName $remoteFile
    }
}
