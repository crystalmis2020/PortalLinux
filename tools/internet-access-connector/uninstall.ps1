param(
    [switch] $Quiet
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$appTitle = 'Support Portal Internet Connector'
$installDirectory = Join-Path $env:LOCALAPPDATA 'SupportPortalConnector'
$settingsKey = 'HKCU:\Software\SupportPortal\InternetAccessConnector'
$protocolKey = 'HKCU:\Software\Classes\supportportal-connect'
$trustedRootThumbprint = 'B00395EF8FDDB938715CE9BDA6674C11189BEA3A'

function Show-UninstallerMessage {
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

$exitCode = 1

try {
    if (-not $Quiet) {
        $answer = Read-Host 'Remove the Support Portal Internet Connector for this user? [y/N]'
        if (-not [string]::Equals($answer, 'y', [StringComparison]::OrdinalIgnoreCase) -and
            -not [string]::Equals($answer, 'yes', [StringComparison]::OrdinalIgnoreCase)) {
            Write-Host 'Uninstall cancelled.'
            exit 0
        }
    }

    $removeProtocolKey = $true
    $commandKey = Join-Path $protocolKey 'shell\open\command'
    if (Test-Path -LiteralPath $commandKey) {
        $registeredCommand = [string] (Get-Item -LiteralPath $commandKey).GetValue('')
        if ($registeredCommand.IndexOf($installDirectory, [StringComparison]::OrdinalIgnoreCase) -lt 0) {
            $removeProtocolKey = $false
        }
    }

    if ($removeProtocolKey -and (Test-Path -LiteralPath $protocolKey)) {
        Remove-Item -LiteralPath $protocolKey -Recurse -Force
    }

    $removeTrustedRoot = $false
    if (Test-Path -LiteralPath $settingsKey) {
        try {
            $settings = Get-ItemProperty -LiteralPath $settingsKey
            $removeTrustedRoot = ([int] $settings.TrustedRootInstalledByConnector -eq 1) -and
                [string]::Equals(
                    [string] $settings.TrustedRootThumbprint,
                    $trustedRootThumbprint,
                    [StringComparison]::OrdinalIgnoreCase
                )
        }
        catch {
            $removeTrustedRoot = $false
        }
    }

    if ($removeTrustedRoot) {
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

    if (Test-Path -LiteralPath $settingsKey) {
        Remove-Item -LiteralPath $settingsKey -Recurse -Force
    }
    if (Test-Path -LiteralPath $installDirectory -PathType Container) {
        Remove-Item -LiteralPath $installDirectory -Recurse -Force
    }

    $exitCode = 0
    if ($removeProtocolKey) {
        $message = 'The connector was removed. Broadband Connection was not changed.'
        if ($removeTrustedRoot) {
            $message += ' The portal root certificate installed by this connector was also removed.'
        }
        Show-UninstallerMessage $message $false
    }
    else {
        Show-UninstallerMessage 'The connector files were removed. The URI protocol registration belonged to a different command and was left unchanged.' $false
    }
}
catch {
    Show-UninstallerMessage ('Uninstall failed: ' + $_.Exception.Message) $true
}

exit $exitCode
