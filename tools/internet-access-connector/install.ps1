param(
    [string] $PortalUrl,
    [switch] $Quiet
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$appTitle = 'Support Portal Internet Connector'
$installDirectory = Join-Path $env:LOCALAPPDATA 'SupportPortalConnector'
$settingsKey = 'HKCU:\Software\SupportPortal\InternetAccessConnector'
$protocolKey = 'HKCU:\Software\Classes\supportportal-connect'
$version = '1.1.0'
$trustedRootThumbprint = 'B00395EF8FDDB938715CE9BDA6674C11189BEA3A'
$scriptDirectory = Split-Path -Parent $MyInvocation.MyCommand.Path
$packagePortalUrlFile = Join-Path $scriptDirectory 'portal-url.txt'
$packageRootCertificate = Join-Path $scriptDirectory 'certs\support-portal-root-ca.cer'

function Show-InstallerMessage {
    param(
        [string] $Message,
        [bool] $IsError
    )

    if ($Quiet) {
        if ($IsError) {
            [Console]::Error.WriteLine($Message)
        }
        else {
            Write-Host $Message
        }
        return
    }

    try {
        $shell = New-Object -ComObject WScript.Shell
        $icon = 64
        if ($IsError) {
            $icon = 16
        }
        [void] $shell.Popup($Message, 0, $appTitle, $icon)
    }
    catch {
        Write-Host $Message
    }
}

function Normalize-PortalUrl {
    param([string] $Value)

    if ([string]::IsNullOrEmpty($Value) -or $Value.Trim().Length -eq 0) {
        throw 'The portal base URL is required.'
    }

    try {
        $candidate = [System.Uri] $Value.Trim()
    }
    catch {
        throw 'The portal base URL is not a valid absolute URL.'
    }

    if (-not $candidate.IsAbsoluteUri) {
        throw 'The portal base URL must be absolute.'
    }
    if (-not [string]::Equals($candidate.Scheme, 'https', [StringComparison]::OrdinalIgnoreCase)) {
        throw 'The portal base URL must use HTTPS.'
    }
    if ([string]::IsNullOrEmpty($candidate.Host)) {
        throw 'The portal base URL must include a host name.'
    }
    if (-not [string]::IsNullOrEmpty($candidate.UserInfo)) {
        throw 'The portal base URL cannot contain user information.'
    }
    if (-not [string]::IsNullOrEmpty($candidate.Query) -or
        -not [string]::IsNullOrEmpty($candidate.Fragment)) {
        throw 'The portal base URL cannot contain a query string or fragment.'
    }

    return $candidate.AbsoluteUri.TrimEnd('/')
}

function Install-PortalRootCertificate {
    param([string] $CertificatePath)

    try {
        $certificate = New-Object System.Security.Cryptography.X509Certificates.X509Certificate2
        $certificate.Import($CertificatePath)
    }
    catch {
        throw 'The packaged Support Portal root certificate is invalid.'
    }

    $actualThumbprint = $certificate.Thumbprint.Replace(' ', '').ToUpperInvariant()
    if (-not [string]::Equals($actualThumbprint, $trustedRootThumbprint, [StringComparison]::Ordinal)) {
        throw 'The packaged Support Portal root certificate did not match the trusted installer fingerprint.'
    }

    $store = New-Object System.Security.Cryptography.X509Certificates.X509Store -ArgumentList @(
        'Root',
        [System.Security.Cryptography.X509Certificates.StoreLocation]::CurrentUser
    )

    try {
        $store.Open([System.Security.Cryptography.X509Certificates.OpenFlags]::ReadWrite)
        $existingCertificates = $store.Certificates.Find(
            [System.Security.Cryptography.X509Certificates.X509FindType]::FindByThumbprint,
            $trustedRootThumbprint,
            $false
        )

        if ($existingCertificates.Count -eq 0) {
            $store.Add($certificate)
            return $true
        }

        return $false
    }
    catch {
        throw 'The Support Portal certificate could not be trusted for this Windows user.'
    }
    finally {
        $store.Close()
    }
}

function Remove-PortalRootCertificate {
    $store = New-Object System.Security.Cryptography.X509Certificates.X509Store -ArgumentList @(
        'Root',
        [System.Security.Cryptography.X509Certificates.StoreLocation]::CurrentUser
    )

    try {
        $store.Open([System.Security.Cryptography.X509Certificates.OpenFlags]::ReadWrite)
        $certificates = $store.Certificates.Find(
            [System.Security.Cryptography.X509Certificates.X509FindType]::FindByThumbprint,
            $trustedRootThumbprint,
            $false
        )
        foreach ($certificate in $certificates) {
            $store.Remove($certificate)
        }
    }
    finally {
        $store.Close()
    }
}

$exitCode = 1
$rootAddedThisRun = $false

try {
    if ([Environment]::OSVersion.Platform -ne [PlatformID]::Win32NT -or
        [Environment]::OSVersion.Version -lt [Version] '6.1') {
        throw 'This connector requires Windows 7 SP1, Windows 10, or Windows 11.'
    }

    if ($PSVersionTable.PSVersion.Major -lt 2) {
        throw 'This connector requires Windows PowerShell 2.0 or later.'
    }

    if ([string]::IsNullOrEmpty($PortalUrl) -and
        (Test-Path -LiteralPath $packagePortalUrlFile -PathType Leaf)) {
        $PortalUrl = [string] (Get-Content -LiteralPath $packagePortalUrlFile | Select-Object -First 1)
    }
    if ([string]::IsNullOrEmpty($PortalUrl) -and -not $Quiet) {
        $PortalUrl = Read-Host 'Enter the HTTPS Support Portal base URL'
    }
    $normalizedPortalUrl = Normalize-PortalUrl $PortalUrl

    $connectorSource = Join-Path $scriptDirectory 'src\connector.ps1'
    $uninstallerSource = Join-Path $scriptDirectory 'uninstall.ps1'
    $uninstallerCommandSource = Join-Path $scriptDirectory 'uninstall.cmd'

    foreach ($requiredFile in @($connectorSource, $uninstallerSource, $uninstallerCommandSource, $packageRootCertificate)) {
        if (-not (Test-Path -LiteralPath $requiredFile -PathType Leaf)) {
            throw ('The package is incomplete. Missing file: ' + $requiredFile)
        }
    }

    if (-not (Test-Path -LiteralPath $installDirectory -PathType Container)) {
        [void] (New-Item -ItemType Directory -Path $installDirectory -Force)
    }

    $connectorTarget = Join-Path $installDirectory 'connector.ps1'
    Copy-Item -LiteralPath $connectorSource -Destination $connectorTarget -Force
    Copy-Item -LiteralPath $uninstallerSource -Destination (Join-Path $installDirectory 'uninstall.ps1') -Force
    Copy-Item -LiteralPath $uninstallerCommandSource -Destination (Join-Path $installDirectory 'uninstall.cmd') -Force
    Copy-Item -LiteralPath $packageRootCertificate -Destination (Join-Path $installDirectory 'support-portal-root-ca.cer') -Force

    $rootOwnedByConnector = $false
    if (Test-Path -LiteralPath $settingsKey) {
        try {
            $existingSettings = Get-ItemProperty -LiteralPath $settingsKey
            $rootOwnedByConnector = ([int] $existingSettings.TrustedRootInstalledByConnector -eq 1) -and
                [string]::Equals(
                    [string] $existingSettings.TrustedRootThumbprint,
                    $trustedRootThumbprint,
                    [StringComparison]::OrdinalIgnoreCase
                )
        }
        catch {
            $rootOwnedByConnector = $false
        }
    }

    $rootAddedThisRun = Install-PortalRootCertificate $packageRootCertificate
    if ($rootAddedThisRun) {
        $rootOwnedByConnector = $true
    }

    [void] (New-Item -Path $settingsKey -Force)
    [void] (New-ItemProperty -Path $settingsKey -Name 'PortalBaseUrl' -Value $normalizedPortalUrl -PropertyType String -Force)
    [void] (New-ItemProperty -Path $settingsKey -Name 'InstalledVersion' -Value $version -PropertyType String -Force)
    [void] (New-ItemProperty -Path $settingsKey -Name 'TrustedRootThumbprint' -Value $trustedRootThumbprint -PropertyType String -Force)
    [void] (New-ItemProperty -Path $settingsKey -Name 'TrustedRootInstalledByConnector' -Value ([int] $rootOwnedByConnector) -PropertyType DWord -Force)

    [void] (New-Item -Path $protocolKey -Force)
    Set-Item -Path $protocolKey -Value 'URL:Support Portal Internet Connector'
    [void] (New-ItemProperty -Path $protocolKey -Name 'URL Protocol' -Value '' -PropertyType String -Force)

    $defaultIconKey = Join-Path $protocolKey 'DefaultIcon'
    [void] (New-Item -Path $defaultIconKey -Force)
    Set-Item -Path $defaultIconKey -Value ('"' + (Join-Path $env:SystemRoot 'System32\rasphone.exe') + '",0')

    $commandKey = Join-Path $protocolKey 'shell\open\command'
    [void] (New-Item -Path $commandKey -Force)
    $powerShellPath = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
    $handlerCommand = '"' + $powerShellPath + '" -NoLogo -NoProfile -NonInteractive -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + $connectorTarget + '" "%1"'
    Set-Item -Path $commandKey -Value $handlerCommand

    $exitCode = 0
    Show-InstallerMessage ("Installed for this Windows user.`r`n`r`nPortal: " + $normalizedPortalUrl + "`r`nConnection: Broadband Connection`r`nPortal certificate: trusted`r`n`r`nRestart the desktop launcher before connecting.") $false
}
catch {
    if ($rootAddedThisRun) {
        try {
            Remove-PortalRootCertificate
        }
        catch {
        }
    }
    Show-InstallerMessage ('Installation failed: ' + $_.Exception.Message) $true
}

exit $exitCode
