param(
    [Parameter(Mandatory = $true, Position = 0)]
    [string] $LaunchUri
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$script:AppTitle = 'Support Portal Internet Connector'
$script:ConnectionName = 'Broadband Connection'
$script:ConnectorVersion = '1.1.0'
$script:SettingsKey = 'HKCU:\Software\SupportPortal\InternetAccessConnector'
$script:MaximumResponseCharacters = 65536

function Show-ConnectorMessage {
    param(
        [string] $Message,
        [bool] $IsError
    )

    try {
        $shell = New-Object -ComObject WScript.Shell
        $icon = 64
        if ($IsError) {
            $icon = 16
        }
        [void] $shell.Popup($Message, 0, $script:AppTitle, $icon)
    }
    catch {
        # The protocol handler normally has no visible console. There is no
        # safe secondary output channel, and this connector never writes logs.
    }
}

function Get-LaunchToken {
    param([string] $Value)

    if ([string]::IsNullOrEmpty($Value) -or $Value.Length -gt 4096) {
        throw 'The Connect link is missing or too long. Return to the portal and try again.'
    }

    try {
        $uri = [System.Uri] $Value
    }
    catch {
        throw 'The Connect link is invalid. Return to the portal and try again.'
    }

    if (-not $uri.IsAbsoluteUri -or
        -not [string]::Equals($uri.Scheme, 'supportportal-connect', [StringComparison]::OrdinalIgnoreCase) -or
        -not [string]::Equals($uri.Host, 'connect', [StringComparison]::OrdinalIgnoreCase) -or
        ($uri.AbsolutePath -ne '' -and $uri.AbsolutePath -ne '/') -or
        -not [string]::IsNullOrEmpty($uri.Fragment) -or
        -not [string]::IsNullOrEmpty($uri.UserInfo)) {
        throw 'The Connect link is invalid. Return to the portal and try again.'
    }

    try {
        [void] [Reflection.Assembly]::LoadWithPartialName('System.Web')
        $query = [System.Web.HttpUtility]::ParseQueryString($uri.Query)
    }
    catch {
        throw 'This computer cannot decode the Connect link.'
    }

    foreach ($key in $query.AllKeys) {
        if (-not [string]::Equals($key, 'token', [StringComparison]::Ordinal)) {
            throw 'The Connect link contains unsupported information.'
        }
    }

    $values = $query.GetValues('token')
    if ($null -eq $values -or $values.Count -ne 1) {
        throw 'The Connect link must contain exactly one token.'
    }

    $token = [string] $values[0]
    if ($token -notmatch '^[A-Za-z0-9_-]{43}$') {
        throw 'The Connect token has an invalid format. Return to the portal and try again.'
    }

    return $token
}

function Get-PortalConfiguration {
    try {
        $portalBaseUrl = [string] (Get-ItemProperty -LiteralPath $script:SettingsKey -Name 'PortalBaseUrl').PortalBaseUrl
        $portalUri = [System.Uri] $portalBaseUrl
    }
    catch {
        throw 'The connector is not configured. Run install.cmd again.'
    }

    if (-not $portalUri.IsAbsoluteUri -or
        -not [string]::Equals($portalUri.Scheme, 'https', [StringComparison]::OrdinalIgnoreCase) -or
        [string]::IsNullOrEmpty($portalUri.Host) -or
        -not [string]::IsNullOrEmpty($portalUri.UserInfo) -or
        -not [string]::IsNullOrEmpty($portalUri.Query) -or
        -not [string]::IsNullOrEmpty($portalUri.Fragment)) {
        throw 'The installed portal address is invalid. Run install.cmd again.'
    }

    $exchangeUri = [System.Uri] ($portalUri.AbsoluteUri.TrimEnd('/') + '/api/internet-access/connector/exchange')

    return @{
        PortalBaseUri = $portalUri
        ExchangeUri = $exchangeUri
    }
}

function ConvertTo-FormBody {
    param([hashtable] $Values)

    [void] [Reflection.Assembly]::LoadWithPartialName('System.Web')
    $parts = New-Object System.Collections.ArrayList
    foreach ($key in $Values.Keys) {
        $encodedKey = [System.Web.HttpUtility]::UrlEncode([string] $key)
        $encodedValue = [System.Web.HttpUtility]::UrlEncode([string] $Values[$key])
        [void] $parts.Add($encodedKey + '=' + $encodedValue)
    }
    return [string]::Join('&', [string[]] $parts.ToArray([string]))
}

function Get-HttpStatusMessage {
    param(
        [int] $StatusCode,
        [bool] $IsVerification
    )

    if ($IsVerification) {
        return ('The portal could not verify the connection immediately (HTTP ' + $StatusCode + ').')
    }

    switch ($StatusCode) {
        400 { return 'The portal rejected the Connect request. Request a new Connect link.' }
        401 { return 'The Connect token is invalid or expired. Request a new Connect link.' }
        403 { return 'This request is not authorized to connect. Return to the portal.' }
        404 { return 'The portal connector endpoint was not found. Contact MIS.' }
        409 { return 'This Connect token was already used. Request a new Connect link.' }
        410 { return 'This Connect token expired. Request a new Connect link.' }
        422 { return 'The portal rejected the Connect request. Request a new Connect link.' }
        429 { return 'Too many Connect attempts were made. Wait briefly and try again.' }
        default { return ('The portal could not issue the connection (HTTP ' + $StatusCode + ').') }
    }
}

function Invoke-PortalPost {
    param(
        [System.Uri] $Uri,
        [string] $Token,
        [string] $Body,
        [bool] $IsVerification
    )

    try {
        $http = New-Object -ComObject 'WinHttp.WinHttpRequest.5.1'
        $http.SetTimeouts(10000, 10000, 30000, 30000)
        $http.Open('POST', $Uri.AbsoluteUri, $false)

        # WinHttpRequestOption_EnableRedirects = 6. Tokens and credentials must
        # never be forwarded to a redirected destination.
        $http.Option(6) = $false

        # WinHttpRequestOption_SecureProtocols = 9 and TLS 1.2 = 0x800.
        # This deliberately fails on an unpatched Windows 7 machine instead of
        # silently transmitting credentials over an obsolete TLS version.
        $http.Option(9) = 2048

        $http.SetRequestHeader('Authorization', 'Bearer ' + $Token)
        $http.SetRequestHeader('Accept', 'application/json')
        $http.SetRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8')
        $http.SetRequestHeader('X-Support-Connector-Version', $script:ConnectorVersion)
        $http.Send($Body)

        $statusCode = [int] $http.Status
        $responseText = [string] $http.ResponseText
        $responseContentType = ''
        try {
            $responseContentType = [string] $http.GetResponseHeader('Content-Type')
        }
        catch {
            # A 204 verification response normally has no Content-Type.
        }
    }
    catch {
        if ($IsVerification) {
            throw 'The portal verification request could not be completed.'
        }
        throw 'The portal could not be reached securely. Check its HTTPS certificate, TLS 1.2 support, and your network connection.'
    }

    if ($responseText.Length -gt $script:MaximumResponseCharacters) {
        if ($IsVerification) {
            throw 'The portal returned an invalid verification response.'
        }
        throw 'The portal returned an unexpectedly large response.'
    }

    return @{
        StatusCode = $statusCode
        Body = $responseText
        ContentType = $responseContentType
    }
}

function ConvertFrom-CredentialResponse {
    param(
        [string] $Json,
        [string] $ContentType,
        [System.Uri] $PortalBaseUri
    )

    if ($ContentType -notmatch '^application/json(?:\s*;|\s*$)') {
        throw 'The portal returned an invalid credential response type.'
    }

    try {
        [void] [Reflection.Assembly]::LoadWithPartialName('System.Web.Extensions')
        $serializer = New-Object System.Web.Script.Serialization.JavaScriptSerializer
        $serializer.MaxJsonLength = $script:MaximumResponseCharacters
        $payload = $serializer.DeserializeObject($Json)
    }
    catch {
        throw 'The portal returned invalid credential data.'
    }

    if ($null -eq $payload -or
        -not ($payload -is [System.Collections.IDictionary]) -or
        -not $payload.ContainsKey('username') -or
        -not $payload.ContainsKey('password') -or
        -not $payload.ContainsKey('verification_url') -or
        -not ($payload['username'] -is [string]) -or
        -not ($payload['password'] -is [string]) -or
        -not ($payload['verification_url'] -is [string])) {
        throw 'The portal credential response is incomplete.'
    }

    $username = [string] $payload['username']
    $password = [string] $payload['password']
    $verificationUrl = [string] $payload['verification_url']

    if ($username.Length -lt 1 -or $username.Length -gt 256 -or
        $password.Length -lt 1 -or $password.Length -gt 256 -or
        $username.IndexOf([char] 0) -ge 0 -or $password.IndexOf([char] 0) -ge 0) {
        throw 'The portal returned invalid PPPoE credentials.'
    }

    try {
        $verificationUri = [System.Uri] $verificationUrl
    }
    catch {
        throw 'The portal returned an invalid verification address.'
    }

    $basePath = $PortalBaseUri.AbsolutePath.TrimEnd('/') + '/'
    if (-not $verificationUri.IsAbsoluteUri -or
        -not [string]::Equals($verificationUri.Scheme, 'https', [StringComparison]::OrdinalIgnoreCase) -or
        -not [string]::Equals($verificationUri.DnsSafeHost, $PortalBaseUri.DnsSafeHost, [StringComparison]::OrdinalIgnoreCase) -or
        $verificationUri.Port -ne $PortalBaseUri.Port -or
        -not [string]::IsNullOrEmpty($verificationUri.UserInfo) -or
        -not [string]::IsNullOrEmpty($verificationUri.Fragment) -or
        -not $verificationUri.AbsolutePath.StartsWith($basePath, [StringComparison]::Ordinal)) {
        throw 'The portal returned a verification address outside the installed portal.'
    }

    return @{
        Username = $username
        Password = $password
        VerificationUri = $verificationUri
    }
}

function Initialize-NativeRas {
    if ('SupportPortal.NativeRas' -as [type]) {
        return
    }

    $source = @'
using System;
using System.Runtime.InteropServices;
using System.Text;

namespace SupportPortal
{
    public static class NativeRas
    {
        private const int RAS_MaxEntryName = 256;
        private const int RAS_MaxPhoneNumber = 128;
        private const int RAS_MaxCallbackNumber = 128;
        private const int UNLEN = 256;
        private const int PWLEN = 256;
        private const int DNLEN = 15;

        [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode, Pack = 4)]
        private struct RASDIALPARAMS
        {
            public int dwSize;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = RAS_MaxEntryName + 1)]
            public string szEntryName;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = RAS_MaxPhoneNumber + 1)]
            public string szPhoneNumber;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = RAS_MaxCallbackNumber + 1)]
            public string szCallbackNumber;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = UNLEN + 1)]
            public string szUserName;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = PWLEN + 1)]
            public string szPassword;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = DNLEN + 1)]
            public string szDomain;

            public int dwSubEntry;
            public UIntPtr dwCallbackId;
            public int dwIfIndex;
        }

        [DllImport("rasapi32.dll", EntryPoint = "RasValidateEntryNameW", CharSet = CharSet.Unicode, ExactSpelling = true)]
        private static extern int RasValidateEntryName(string phonebook, string entryName);

        [DllImport("rasapi32.dll", EntryPoint = "RasDialW", CharSet = CharSet.Unicode, ExactSpelling = true)]
        private static extern int RasDial(
            IntPtr extensions,
            string phonebook,
            ref RASDIALPARAMS parameters,
            int notifierType,
            IntPtr notifier,
            ref IntPtr connection);

        [DllImport("rasapi32.dll", EntryPoint = "RasGetErrorStringW", CharSet = CharSet.Unicode, ExactSpelling = true)]
        private static extern int RasGetErrorString(int errorCode, StringBuilder errorText, int bufferSize);

        public static int ValidateEntry(string phonebook, string entryName)
        {
            return RasValidateEntryName(phonebook, entryName);
        }

        public static int Dial(string phonebook, string entryName, string username, string password)
        {
            RASDIALPARAMS parameters = new RASDIALPARAMS();
            parameters.dwSize = Marshal.SizeOf(typeof(RASDIALPARAMS));
            parameters.szEntryName = entryName;
            parameters.szPhoneNumber = String.Empty;
            parameters.szCallbackNumber = String.Empty;
            parameters.szUserName = username;
            parameters.szPassword = password;
            parameters.szDomain = String.Empty;
            parameters.dwSubEntry = 0;
            parameters.dwCallbackId = UIntPtr.Zero;
            parameters.dwIfIndex = 0;

            IntPtr connection = IntPtr.Zero;
            try
            {
                return RasDial(IntPtr.Zero, phonebook, ref parameters, 0, IntPtr.Zero, ref connection);
            }
            finally
            {
                parameters.szUserName = String.Empty;
                parameters.szPassword = String.Empty;
            }
        }

        public static string ErrorText(int errorCode)
        {
            StringBuilder text = new StringBuilder(512);
            int result = RasGetErrorString(errorCode, text, text.Capacity);
            if (result == 0)
            {
                return text.ToString();
            }
            return String.Empty;
        }
    }
}
'@

    try {
        Add-Type -TypeDefinition $source -Language CSharp
    }
    catch {
        throw 'Windows RAS support could not be loaded on this computer.'
    }
}

function Get-RasPhonebook {
    # ERROR_ALREADY_EXISTS (183) means the entry is present. Check the current
    # default phonebook first, then the standard per-user and all-user files.
    $defaultResult = [SupportPortal.NativeRas]::ValidateEntry($null, $script:ConnectionName)
    if ($defaultResult -eq 183) {
        return @{ Phonebook = $null }
    }

    $candidatePhonebooks = New-Object System.Collections.ArrayList
    if (-not [string]::IsNullOrEmpty($env:APPDATA)) {
        [void] $candidatePhonebooks.Add((Join-Path $env:APPDATA 'Microsoft\Network\Connections\Pbk\rasphone.pbk'))
    }
    if (-not [string]::IsNullOrEmpty($env:ProgramData)) {
        [void] $candidatePhonebooks.Add((Join-Path $env:ProgramData 'Microsoft\Network\Connections\Pbk\rasphone.pbk'))
    }

    foreach ($phonebook in $candidatePhonebooks) {
        if ((Test-Path -LiteralPath $phonebook -PathType Leaf) -and
            [SupportPortal.NativeRas]::ValidateEntry([string] $phonebook, $script:ConnectionName) -eq 183) {
            return @{ Phonebook = [string] $phonebook }
        }
    }

    throw 'Broadband Connection was not found on this computer.'
}

function Get-RasFailureMessage {
    param([int] $Code)

    switch ($Code) {
        623 { return 'Broadband Connection was not found on this computer.' }
        633 { return 'The broadband device is already in use. Disconnect the existing connection and try again.' }
        676 { return 'The broadband connection is busy. Wait briefly and try again.' }
        691 { return 'MikroTik rejected the temporary PPPoE credentials. Request a new Connect link.' }
        756 { return 'Broadband Connection is already being dialed.' }
        813 { return 'Another broadband connection is using the same device.' }
    }

    $nativeMessage = [SupportPortal.NativeRas]::ErrorText($Code)
    if ([string]::IsNullOrEmpty($nativeMessage)) {
        return ('Windows could not connect Broadband Connection (RAS error ' + $Code + ').')
    }
    return ('Windows could not connect Broadband Connection (RAS error ' + $Code + ': ' + $nativeMessage.Trim() + ').')
}

$exitCode = 1
$mutex = $null
$hasMutex = $false
$token = $null
$credentials = $null
$username = $null
$password = $null

try {
    $mutex = New-Object System.Threading.Mutex -ArgumentList @($false, 'Local\SupportPortalInternetConnector')
    $hasMutex = $mutex.WaitOne(0, $false)
    if (-not $hasMutex) {
        throw 'Another Connect attempt is already running.'
    }

    Initialize-NativeRas

    # Locate the connection before consuming the one-time token. Explicitly
    # support both per-user and all-user Windows phonebooks.
    $rasPhonebook = Get-RasPhonebook

    $token = Get-LaunchToken $LaunchUri
    $configuration = Get-PortalConfiguration
    $exchangeBody = ConvertTo-FormBody @{
        connector_version = $script:ConnectorVersion
        connection_name = $script:ConnectionName
    }
    $exchangeResponse = Invoke-PortalPost $configuration.ExchangeUri $token $exchangeBody $false

    if ($exchangeResponse.StatusCode -ne 200) {
        throw (Get-HttpStatusMessage $exchangeResponse.StatusCode $false)
    }

    $credentials = ConvertFrom-CredentialResponse $exchangeResponse.Body $exchangeResponse.ContentType $configuration.PortalBaseUri
    $username = [string] $credentials.Username
    $password = [string] $credentials.Password

    $rasResult = [SupportPortal.NativeRas]::Dial($rasPhonebook.Phonebook, $script:ConnectionName, $username, $password)

    # Release managed references as soon as RasDial returns. See README for the
    # managed-memory limitation; this does not claim cryptographic zeroization.
    $username = $null
    $password = $null
    $exchangeResponse = $null
    [GC]::Collect()

    if ($rasResult -ne 0) {
        throw (Get-RasFailureMessage $rasResult)
    }

    $verificationBody = ConvertTo-FormBody @{
        connector_version = $script:ConnectorVersion
        connection_name = $script:ConnectionName
        ras_result = '0'
    }

    try {
        $verificationResponse = Invoke-PortalPost $credentials.VerificationUri $token $verificationBody $true
        if ($verificationResponse.StatusCode -ne 200 -and
            $verificationResponse.StatusCode -ne 202 -and
            $verificationResponse.StatusCode -ne 204) {
            throw (Get-HttpStatusMessage $verificationResponse.StatusCode $true)
        }

        Show-ConnectorMessage 'Broadband Connection is connected. The portal is verifying internet access.' $false
    }
    catch {
        Show-ConnectorMessage ('Broadband Connection is connected, but the portal could not verify it immediately. The portal will check again automatically. Do not reconnect.' + "`r`n`r`n" + $_.Exception.Message) $true
    }

    $exitCode = 0
}
catch {
    Show-ConnectorMessage $_.Exception.Message $true
}
finally {
    $username = $null
    $password = $null
    $credentials = $null
    $token = $null

    if ($hasMutex -and $null -ne $mutex) {
        try {
            $mutex.ReleaseMutex()
        }
        catch {
        }
    }
    if ($null -ne $mutex) {
        $mutex.Close()
    }
}

exit $exitCode
