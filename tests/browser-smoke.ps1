[CmdletBinding()]
param(
    [string] $BaseUrl = 'http://localhost/aspect-trading',
    [string] $ChromePath = 'C:\Program Files\Google\Chrome\Application\chrome.exe',
    [string] $ArtifactDirectory = (Join-Path $PSScriptRoot '..\.artifacts\browser-smoke'),
    [switch] $SkipScreenshots,
    [switch] $WorkflowOnly
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$script:Sequence = 0
$script:Events = [System.Collections.Generic.List[object]]::new()

function Receive-CdpMessage {
    param([System.Net.WebSockets.ClientWebSocket] $Socket)

    $buffer = New-Object byte[] 1048576
    $stream = [System.IO.MemoryStream]::new()

    do {
        $segment = [System.ArraySegment[byte]]::new($buffer)
        $timeout = [System.Threading.CancellationTokenSource]::new([TimeSpan]::FromSeconds(30))
        try {
            $result = $Socket.ReceiveAsync($segment, $timeout.Token).GetAwaiter().GetResult()
        } catch [System.OperationCanceledException] {
            throw 'Timed out while waiting for a Chrome DevTools message.'
        } finally {
            $timeout.Dispose()
        }

        if ($result.MessageType -eq [System.Net.WebSockets.WebSocketMessageType]::Close) {
            throw 'Chrome closed the DevTools connection unexpectedly.'
        }

        $stream.Write($buffer, 0, $result.Count)
    } while (-not $result.EndOfMessage)

    $json = [System.Text.Encoding]::UTF8.GetString($stream.ToArray())
    $stream.Dispose()
    return $json | ConvertFrom-Json
}

function Send-CdpCommand {
    param(
        [System.Net.WebSockets.ClientWebSocket] $Socket,
        [string] $Method,
        [hashtable] $Parameters = @{}
    )

    $script:Sequence++
    $id = $script:Sequence
    $payload = @{ id = $id; method = $Method; params = $Parameters } | ConvertTo-Json -Compress -Depth 20
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($payload)
    $segment = [System.ArraySegment[byte]]::new($bytes)
    $Socket.SendAsync($segment, [System.Net.WebSockets.WebSocketMessageType]::Text, $true, [System.Threading.CancellationToken]::None).GetAwaiter().GetResult() | Out-Null

    while ($true) {
        try {
            $message = Receive-CdpMessage -Socket $Socket
        } catch {
            throw "Chrome DevTools did not respond to ${Method}: $($_.Exception.Message)"
        }

        if ($message.PSObject.Properties.Name -contains 'id' -and $message.id -eq $id) {
            if ($message.PSObject.Properties.Name -contains 'error') {
                throw "Chrome DevTools error for ${Method}: $($message.error.message)"
            }

            return $message.result
        }

        $script:Events.Add($message)
    }
}

function Wait-ForPageLoad {
    param(
        [System.Net.WebSockets.ClientWebSocket] $Socket,
        [int] $TimeoutSeconds = 30
    )

    $deadline = [DateTime]::UtcNow.AddSeconds($TimeoutSeconds)

    while ([DateTime]::UtcNow -lt $deadline) {
        $message = Receive-CdpMessage -Socket $Socket
        $script:Events.Add($message)

        if ($message.PSObject.Properties.Name -contains 'method' -and $message.method -eq 'Page.loadEventFired') {
            return
        }
    }

    throw 'Timed out while waiting for the page load event.'
}

function Get-BrowserIssues {
    param([object[]] $Events)

    $issues = [System.Collections.Generic.List[object]]::new()

    foreach ($event in $Events) {
        if (-not ($event.PSObject.Properties.Name -contains 'method')) {
            continue
        }

        switch ($event.method) {
            'Runtime.exceptionThrown' {
                $issues.Add([pscustomobject]@{ type = 'exception'; message = $event.params.exceptionDetails.text })
            }
            'Log.entryAdded' {
                if ($event.params.entry.level -in @('error', 'warning')) {
                    $issues.Add([pscustomobject]@{ type = "log-$($event.params.entry.level)"; message = $event.params.entry.text })
                }
            }
            'Network.loadingFailed' {
                if (-not $event.params.canceled) {
                    $issues.Add([pscustomobject]@{ type = 'request-failed'; message = "$($event.params.errorText) ($($event.params.type))" })
                }
            }
            'Network.responseReceived' {
                if ([int] $event.params.response.status -ge 400) {
                    $issues.Add([pscustomobject]@{ type = "http-$([int] $event.params.response.status)"; message = [string] $event.params.response.url })
                }
            }
        }
    }

    return @($issues)
}

function Invoke-BrowserExpression {
    param(
        [System.Net.WebSockets.ClientWebSocket] $Socket,
        [string] $Expression
    )

    $evaluation = Send-CdpCommand -Socket $Socket -Method 'Runtime.evaluate' -Parameters @{
        expression = $Expression
        returnByValue = $true
        awaitPromise = $true
    }
    return $evaluation.result.value
}

function Wait-BrowserCondition {
    param(
        [System.Net.WebSockets.ClientWebSocket] $Socket,
        [string] $Expression,
        [int] $Attempts = 40
    )

    for ($attempt = 0; $attempt -lt $Attempts; $attempt++) {
        try {
            if (Invoke-BrowserExpression -Socket $Socket -Expression $Expression) {
                return $true
            }
        } catch {
            # Page navigation can briefly replace the JavaScript execution context.
        }
        Start-Sleep -Milliseconds 250
    }
    return $false
}

function Inspect-Page {
    param(
        [System.Net.WebSockets.ClientWebSocket] $Socket,
        [string] $Url,
        [int] $Width,
        [switch] $CaptureScreenshot
    )

    $script:Events.Clear()
    Send-CdpCommand -Socket $Socket -Method 'Emulation.setDeviceMetricsOverride' -Parameters @{
        width = $Width
        height = 1000
        deviceScaleFactor = 1
        mobile = $false
        screenWidth = $Width
        screenHeight = 1000
    } | Out-Null
    Send-CdpCommand -Socket $Socket -Method 'Page.navigate' -Parameters @{ url = $Url } | Out-Null

    $loaded = $false
    for ($attempt = 0; $attempt -lt 120; $attempt++) {
        $readyState = Send-CdpCommand -Socket $Socket -Method 'Runtime.evaluate' -Parameters @{
            expression = 'document.readyState'
            returnByValue = $true
        }
        if ($readyState.result.value -eq 'complete') {
            $loaded = $true
            break
        }
        Start-Sleep -Milliseconds 250
    }
    if (-not $loaded) {
        throw "Timed out while loading $Url"
    }
    Start-Sleep -Milliseconds 750

    $expression = @'
(() => {
    const root = document.documentElement;
    const body = document.body;
    const viewportWidth = window.innerWidth;
    const documentWidth = Math.max(root.scrollWidth, body ? body.scrollWidth : 0);
    const offenders = [...document.querySelectorAll('body *')]
        .map((element) => {
            const rect = element.getBoundingClientRect();
            return {
                tag: element.tagName.toLowerCase(),
                className: typeof element.className === 'string' ? element.className.slice(0, 120) : '',
                left: Math.round(rect.left * 10) / 10,
                right: Math.round(rect.right * 10) / 10,
                width: Math.round(rect.width * 10) / 10
            };
        })
        .filter((item) => item.width > 0 && (item.left < -1 || item.right > viewportWidth + 1))
        .sort((a, b) => (b.right - viewportWidth) - (a.right - viewportWidth))
        .slice(0, 12);

    return {
        url: location.href,
        title: document.title,
        statusText: document.querySelector('main') ? 'main-present' : 'main-missing',
        viewportWidth,
        clientWidth: root.clientWidth,
        documentWidth,
        hasHorizontalOverflow: documentWidth > root.clientWidth + 1,
        offenders,
        productCards: document.querySelectorAll('.aspect-product, .products .product').length,
        categoryCards: document.querySelectorAll('.aspect-category-card').length,
        mobileMenuVisible: getComputedStyle(document.querySelector('.aspect-mobile-menu') || document.body).display !== 'none',
        singleProductTitle: (document.querySelector('h1.product_title, .product_title.entry-title') || {}).textContent?.trim() || '',
        variationForms: document.querySelectorAll('form.variations_form').length,
        variationSelects: document.querySelectorAll('form.variations_form select').length,
        addToCartButtons: document.querySelectorAll('.single_add_to_cart_button, .aspect-product__add').length,
        cartItems: document.querySelectorAll('.woocommerce-cart-form__cart-item, .wc-block-cart-items__row').length,
        checkoutActions: document.querySelectorAll('.checkout-button, .wc-block-cart__submit-button').length,
        checkoutForms: document.querySelectorAll('form.checkout, .wc-block-checkout, .woocommerce-checkout').length,
        catalogFilterPanels: document.querySelectorAll('.aspect-catalog-filters').length,
        catalogFilterOpen: Boolean(document.querySelector('.aspect-catalog-filters')?.open)
    };
})()
'@

    $evaluation = Send-CdpCommand -Socket $Socket -Method 'Runtime.evaluate' -Parameters @{
        expression = $expression
        returnByValue = $true
        awaitPromise = $true
    }
    $page = $evaluation.result.value

    if ($CaptureScreenshot) {
        $safeName = ([uri] $page.url).AbsolutePath.Trim('/').Replace('/', '-')
        if (-not $safeName) { $safeName = 'home' }
        $screenshot = Send-CdpCommand -Socket $Socket -Method 'Page.captureScreenshot' -Parameters @{
            format = 'png'
            captureBeyondViewport = $false
        }
        $path = Join-Path $ArtifactDirectory "$safeName-$Width.png"
        [System.IO.File]::WriteAllBytes($path, [Convert]::FromBase64String($screenshot.data))
    }

    return [pscustomobject]@{
        width = $Width
        url = $page.url
        title = $page.title
        statusText = $page.statusText
        viewportWidth = $page.viewportWidth
        clientWidth = $page.clientWidth
        documentWidth = $page.documentWidth
        hasHorizontalOverflow = $page.hasHorizontalOverflow
        offenders = @($page.offenders)
        productCards = $page.productCards
        categoryCards = $page.categoryCards
        mobileMenuVisible = $page.mobileMenuVisible
        singleProductTitle = $page.singleProductTitle
        variationForms = $page.variationForms
        variationSelects = $page.variationSelects
        addToCartButtons = $page.addToCartButtons
        cartItems = $page.cartItems
        checkoutActions = $page.checkoutActions
        checkoutForms = $page.checkoutForms
        catalogFilterPanels = $page.catalogFilterPanels
        catalogFilterOpen = $page.catalogFilterOpen
        browserIssues = @(Get-BrowserIssues -Events $script:Events)
    }
}

if (-not (Test-Path -LiteralPath $ChromePath)) {
    throw "Chrome was not found at $ChromePath"
}

New-Item -ItemType Directory -Path $ArtifactDirectory -Force | Out-Null
$profileDirectory = Join-Path $ArtifactDirectory ("chrome-profile-{0}" -f [guid]::NewGuid().ToString('N'))
$portProbe = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, 0)
$portProbe.Start()
$debugPort = ([System.Net.IPEndPoint] $portProbe.LocalEndpoint).Port
$portProbe.Stop()
$chrome = $null
$socket = $null

try {
    $chrome = Start-Process -FilePath $ChromePath -ArgumentList @(
        '--headless',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        "--remote-debugging-port=$debugPort",
        "--user-data-dir=$profileDirectory",
        'about:blank'
    ) -PassThru -WindowStyle Hidden

    $versionUrl = "http://127.0.0.1:$debugPort/json/version"
    $debugReady = $false
    for ($attempt = 0; $attempt -lt 60; $attempt++) {
        try {
            Invoke-RestMethod -Uri $versionUrl -TimeoutSec 1 | Out-Null
            $debugReady = $true
            break
        } catch {
            Start-Sleep -Milliseconds 250
        }
    }
    if (-not $debugReady) {
        throw "Chrome did not open its debugging endpoint on port $debugPort."
    }

    $targets = Invoke-RestMethod -Uri "http://127.0.0.1:$debugPort/json/list"
    $target = $targets | Where-Object { $_.type -eq 'page' -and $_.url -eq 'about:blank' } | Select-Object -First 1
    if (-not $target) {
        $target = $targets | Where-Object { $_.type -eq 'page' -and $_.url -notlike 'chrome-extension://*' } | Select-Object -First 1
    }
    if (-not $target) {
        throw 'Chrome did not expose a debuggable page target.'
    }
    Write-Verbose "Selected Chrome target: type=$($target.type), url=$($target.url), id=$($target.id)."

    $webSocketUrl = [string] (@($target.webSocketDebuggerUrl)[0])
    $socket = [System.Net.WebSockets.ClientWebSocket]::new()
    $socket.ConnectAsync([uri] $webSocketUrl, [System.Threading.CancellationToken]::None).GetAwaiter().GetResult() | Out-Null
    Send-CdpCommand -Socket $socket -Method 'Page.enable' | Out-Null
    Send-CdpCommand -Socket $socket -Method 'Runtime.enable' | Out-Null
    Send-CdpCommand -Socket $socket -Method 'Log.enable' | Out-Null
    Send-CdpCommand -Socket $socket -Method 'Network.enable' | Out-Null

    $results = [System.Collections.Generic.List[object]]::new()
    $viewportWidths = @(320, 360, 375, 390, 430, 768, 1024, 1280, 1440, 1920)

    if (-not $WorkflowOnly) {
        foreach ($width in $viewportWidths) {
            Write-Verbose "Inspecting home at ${width}px."
            $capture = -not $SkipScreenshots -and $width -in @(390, 1440)
            $results.Add((Inspect-Page -Socket $socket -Url "$BaseUrl/" -Width $width -CaptureScreenshot:$capture))
        }

        $routeChecks = @(
            @{ route = '/shop/'; width = 390; capture = $true },
            @{ route = '/shop/'; width = 1440; capture = $true },
            @{ route = '/shop/?aspect_stock=outofstock'; width = 390; capture = $false },
            @{ route = '/shop/?aspect_stock=outofstock'; width = 1440; capture = $false },
            @{ route = '/cart/'; width = 390; capture = $false },
            @{ route = '/my-account/'; width = 390; capture = $false },
            @{ route = '/?s=wireless&post_type=product'; width = 390; capture = $false },
            @{ route = '/product-category/kitchenware/'; width = 390; capture = $false },
            @{ route = '/product/wireless-over-ear-headphones/'; width = 1440; capture = $true },
            @{ route = '/product/wireless-over-ear-headphones/'; width = 390; capture = $true },
            @{ route = '/product/cotton-duvet-cover-set/'; width = 1440; capture = $true },
            @{ route = '/product/cotton-duvet-cover-set/'; width = 390; capture = $true }
        )

        foreach ($check in $routeChecks) {
            Write-Verbose "Inspecting $($check.route) at $($check.width)px."
            $capture = -not $SkipScreenshots -and $check.capture
            $results.Add((Inspect-Page -Socket $socket -Url ($BaseUrl.TrimEnd('/') + $check.route) -Width $check.width -CaptureScreenshot:$capture))
        }
    }

    $workflow = [ordered]@{}
    Write-Verbose 'Testing simple product add to cart.'
    $simpleProduct = Inspect-Page -Socket $socket -Url "$BaseUrl/product/wireless-over-ear-headphones/" -Width 390
    $workflow.simplePage = [ordered]@{
        url = $simpleProduct.url
        title = $simpleProduct.title
        addToCartButtons = $simpleProduct.addToCartButtons
        browserIssues = @($simpleProduct.browserIssues)
    }
    $workflow.simpleDom = [string] (Invoke-BrowserExpression -Socket $socket -Expression @'
JSON.stringify({
    url: location.href,
    readyState: document.readyState,
    forms: document.querySelectorAll('form.cart').length,
    buttons: document.querySelectorAll('.single_add_to_cart_button').length,
    bodyClass: document.body?.className || ''
})
'@)
    $workflow.simpleSubmitted = [bool] (Invoke-BrowserExpression -Socket $socket -Expression @'
(async () => {
    const form = document.querySelector('form.cart');
    const button = form?.querySelector('.single_add_to_cart_button');
    if (!form || !button) return false;
    const body = new FormData(form);
    body.set('add-to-cart', button.value);
    const response = await fetch(form.action || location.href, { method: 'POST', body, credentials: 'same-origin' });
    return response.ok;
})()
'@)
    $simpleCart = Inspect-Page -Socket $socket -Url "$BaseUrl/cart/" -Width 390
    $results.Add($simpleCart)
    $workflow.simpleCartItems = $simpleCart.cartItems
    if (-not $workflow.simpleSubmitted -or $simpleCart.cartItems -lt 1) {
        throw "Simple product add-to-cart workflow failed (submitted=$($workflow.simpleSubmitted), cartItems=$($simpleCart.cartItems), dom=$($workflow.simpleDom))."
    }

    Write-Verbose 'Testing variable selection and add to cart.'
    Inspect-Page -Socket $socket -Url "$BaseUrl/product/cotton-duvet-cover-set/" -Width 390 | Out-Null
    $workflow.variableOption = [string] (Invoke-BrowserExpression -Socket $socket -Expression @'
(() => {
    const select = document.querySelector('form.variations_form select');
    const option = select ? [...select.options].find((item) => item.value) : null;
    if (!select || !option) return '';
    select.value = option.value;
    select.dispatchEvent(new Event('change', { bubbles: true }));
    return option.value;
})()
'@)
    $workflow.variableReady = Wait-BrowserCondition -Socket $socket -Expression @'
(() => {
    const form = document.querySelector('form.variations_form');
    const button = form?.querySelector('.single_add_to_cart_button');
    const variationId = form?.querySelector('input[name="variation_id"]')?.value;
    return Boolean(button && !button.disabled && variationId);
})()
'@
    $variableResponseJson = [string] (Invoke-BrowserExpression -Socket $socket -Expression @'
(async () => {
    const form = document.querySelector('form.variations_form');
    const button = form?.querySelector('.single_add_to_cart_button');
    if (!form || !button || button.disabled) return JSON.stringify({ ok: false, reason: 'form-or-button-unavailable' });
    const body = new FormData(form);
    body.set('add-to-cart', button.value || body.get('product_id'));
    const response = await fetch(form.action || location.href, { method: 'POST', body, credentials: 'same-origin' });
    const html = await response.text();
    const parsed = new DOMParser().parseFromString(html, 'text/html');
    return JSON.stringify({
        ok: response.ok,
        status: response.status,
        responseUrl: response.url,
        fields: Object.fromEntries(body.entries()),
        notices: [...parsed.querySelectorAll('.woocommerce-error li, .woocommerce-error, .woocommerce-message')]
            .map((item) => item.textContent.trim()).filter(Boolean).slice(0, 4)
    });
})()
'@)
    $workflow.variableResponse = $variableResponseJson
    $variableResponse = $variableResponseJson | ConvertFrom-Json
    $workflow.variableSubmitted = [bool] $variableResponse.ok
    $variableCart = Inspect-Page -Socket $socket -Url "$BaseUrl/cart/" -Width 390
    $results.Add($variableCart)
    $workflow.variableCartItems = $variableCart.cartItems
    if (-not $workflow.variableOption -or -not $workflow.variableReady -or -not $workflow.variableSubmitted -or $variableCart.cartItems -lt 2) {
        throw "Variable product selection or add-to-cart workflow failed (option=$($workflow.variableOption), ready=$($workflow.variableReady), submitted=$($workflow.variableSubmitted), cartItems=$($variableCart.cartItems), response=$variableResponseJson)."
    }

    Write-Verbose 'Testing cart item removal.'
    $workflow.removeControlReady = Wait-BrowserCondition -Socket $socket -Attempts 80 -Expression "Boolean(document.querySelector('.woocommerce-cart-form__cart-item a.remove, .product-remove a, .wc-block-cart-item__remove-link, a.remove_from_cart_button'))"
    $workflow.removeControls = [string] (Invoke-BrowserExpression -Socket $socket -Expression @'
JSON.stringify([...document.querySelectorAll('a, button')]
    .filter((item) => /remove/i.test(`${item.className} ${item.getAttribute('name') || ''} ${item.getAttribute('aria-label') || ''} ${item.textContent || ''}`))
    .slice(0, 8)
    .map((item) => ({ tag: item.tagName.toLowerCase(), className: item.className, label: item.getAttribute('aria-label') || item.textContent.trim() })))
'@)
    $removePoint = Invoke-BrowserExpression -Socket $socket -Expression @'
(() => {
    const control = document.querySelector('.woocommerce-cart-form__cart-item a.remove, .product-remove a, .wc-block-cart-item__remove-link, a.remove_from_cart_button');
    if (!control) return { found: false };
    control.scrollIntoView({ block: 'center' });
    const rect = control.getBoundingClientRect();
    return { found: true, x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
})()
'@
    $workflow.removeClicked = [bool] $removePoint.found
    if (-not $workflow.removeControlReady -or -not $workflow.removeClicked) {
        throw "Cart removal control was not found (controls=$($workflow.removeControls))."
    }
    Send-CdpCommand -Socket $socket -Method 'Input.dispatchMouseEvent' -Parameters @{ type = 'mousePressed'; x = [double] $removePoint.x; y = [double] $removePoint.y; button = 'left'; clickCount = 1 } | Out-Null
    Send-CdpCommand -Socket $socket -Method 'Input.dispatchMouseEvent' -Parameters @{ type = 'mouseReleased'; x = [double] $removePoint.x; y = [double] $removePoint.y; button = 'left'; clickCount = 1 } | Out-Null
    $workflow.removeObserved = Wait-BrowserCondition -Socket $socket -Attempts 160 -Expression "document.querySelectorAll('.woocommerce-cart-form__cart-item, .wc-block-cart-items__row').length < $($variableCart.cartItems)"
    $workflow.removeIssues = @(Get-BrowserIssues -Events $script:Events)
    $workflow.removeState = [string] (Invoke-BrowserExpression -Socket $socket -Expression @'
JSON.stringify({
    rows: document.querySelectorAll('.woocommerce-cart-form__cart-item, .wc-block-cart-items__row').length,
    notices: [...document.querySelectorAll('.wc-block-components-notice-banner, .woocommerce-error, .woocommerce-message')].map((item) => item.textContent.trim()).filter(Boolean),
    buttons: [...document.querySelectorAll('.wc-block-cart-item__remove-link')].map((item) => ({ disabled: item.disabled, label: item.getAttribute('aria-label') })),
    spinners: document.querySelectorAll('.wc-block-components-spinner').length
})
'@)
    if (-not $workflow.removeObserved) {
        $removeIssuesJson = $workflow.removeIssues | ConvertTo-Json -Compress -Depth 8
        throw "Cart item removal did not update the live cart (state=$($workflow.removeState), issues=$removeIssuesJson)."
    }
    Start-Sleep -Seconds 5
    Invoke-BrowserExpression -Socket $socket -Expression 'true' | Out-Null
    $removeNetworkEvents = @($script:Events | Where-Object {
        $_.method -eq 'Network.responseReceived' -and $_.params.response.url -like '*wc/store*'
    })
    $workflow.removeNetwork = @($removeNetworkEvents | ForEach-Object {
        [ordered]@{ status = [int] $_.params.response.status; url = [string] $_.params.response.url }
    })
    $workflow.removeNetworkBody = ''
    if ($removeNetworkEvents.Count -gt 0) {
        try {
            $lastStoreResponse = $removeNetworkEvents[$removeNetworkEvents.Count - 1]
            $responseBody = Send-CdpCommand -Socket $socket -Method 'Network.getResponseBody' -Parameters @{ requestId = [string] $lastStoreResponse.params.requestId }
            $decodedResponse = [string] $responseBody.body | ConvertFrom-Json
            $workflow.removeNetworkBody = @($decodedResponse.responses | ForEach-Object {
                [ordered]@{
                    status = [int] $_.status
                    itemsCount = [int] $_.body.items_count
                    errors = @($_.body.errors)
                }
            })
        } catch {
            $workflow.removeNetworkBody = "Unavailable: $($_.Exception.Message)"
        }
    }
    $reducedCart = Inspect-Page -Socket $socket -Url "$BaseUrl/cart/" -Width 390
    $results.Add($reducedCart)
    $workflow.cartItemsAfterRemove = $reducedCart.cartItems
    if (-not $workflow.removeObserved -or $reducedCart.cartItems -ge $variableCart.cartItems) {
        $removeNetworkJson = $workflow.removeNetwork | ConvertTo-Json -Compress -Depth 8
        throw "Cart item removal workflow failed (clicked=$($workflow.removeClicked), observed=$($workflow.removeObserved), before=$($variableCart.cartItems), after=$($reducedCart.cartItems), network=$removeNetworkJson, body=$($workflow.removeNetworkBody))."
    }

    Write-Verbose 'Testing checkout rendering.'
    $checkout = Inspect-Page -Socket $socket -Url "$BaseUrl/checkout/" -Width 390
    $results.Add($checkout)
    $workflow.checkoutForms = $checkout.checkoutForms
    if ($checkout.checkoutForms -lt 1) {
        throw 'Checkout route did not render a checkout form.'
    }

    $report = [pscustomobject]@{
        generatedAt = [DateTime]::UtcNow.ToString('o')
        baseUrl = $BaseUrl
        results = $results
        commerceWorkflow = $workflow
    }
    $json = $report | ConvertTo-Json -Depth 20
    [System.IO.File]::WriteAllText((Join-Path $ArtifactDirectory 'report.json'), $json)
    $json
} finally {
    if ($socket) {
        $socket.Dispose()
    }
    if ($chrome -and -not $chrome.HasExited) {
        Stop-Process -Id $chrome.Id -Force
    }
    $artifactRoot = [System.IO.Path]::GetFullPath($ArtifactDirectory).TrimEnd([System.IO.Path]::DirectorySeparatorChar) + [System.IO.Path]::DirectorySeparatorChar
    $profileRoot = [System.IO.Path]::GetFullPath($profileDirectory)
    if ($profileRoot.StartsWith($artifactRoot, [System.StringComparison]::OrdinalIgnoreCase) -and (Test-Path -LiteralPath $profileRoot)) {
        for ($cleanupAttempt = 0; $cleanupAttempt -lt 5 -and (Test-Path -LiteralPath $profileRoot); $cleanupAttempt++) {
            Start-Sleep -Milliseconds 500
            Remove-Item -LiteralPath $profileRoot -Recurse -Force -ErrorAction SilentlyContinue
        }
        if (Test-Path -LiteralPath $profileRoot) {
            Write-Verbose "Chrome profile cleanup is deferred because a Chrome child process still holds a file: $profileRoot"
        }
    }
}
