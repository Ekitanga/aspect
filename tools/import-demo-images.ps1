$ErrorActionPreference = 'Stop'

$siteRoot = 'C:\xampp\htdocs\aspect-trading'
$php = 'C:\xampp\php\php.exe'
$wpCli = Join-Path $env:TEMP 'aspect-wp-cli.phar'
$downloadRoot = Join-Path $env:TEMP 'aspect-trading-demo-images'
$siteUrl = 'http://localhost/aspect-trading/'

$images = @(
    @{ Key = 'phones'; Photo = 'photo-1511707171634-5f897ff02aa9'; Title = 'Development demo smartphone'; Alt = 'Generic smartphone photographed for Aspect Trading development demo' },
    @{ Key = 'computing'; Photo = 'photo-1496181133206-80ce9b88a853'; Title = 'Development demo laptop'; Alt = 'Generic laptop photographed for Aspect Trading development demo' },
    @{ Key = 'audio'; Photo = 'photo-1505740420928-5e560c06d30e'; Title = 'Development demo headphones'; Alt = 'Generic headphones photographed for Aspect Trading development demo' },
    @{ Key = 'fashion'; Photo = 'photo-1521572163474-6864f9cf17ab'; Title = 'Development demo clothing'; Alt = 'Generic folded clothing photographed for Aspect Trading development demo' },
    @{ Key = 'sports'; Photo = 'photo-1542291026-7eec264c27ff'; Title = 'Development demo running shoe'; Alt = 'Generic running shoe photographed for Aspect Trading development demo' },
    @{ Key = 'home-office'; Photo = 'photo-1497366754035-f200968a6e72'; Title = 'Development demo home office'; Alt = 'Generic home office photographed for Aspect Trading development demo' },
    @{ Key = 'appliances'; Photo = 'photo-1556911220-bff31c812dba'; Title = 'Development demo kitchen'; Alt = 'Generic kitchen photographed for Aspect Trading development demo' },
    @{ Key = 'beauty'; Photo = 'photo-1556229010-6c3f2c9ca5f8'; Title = 'Development demo skincare'; Alt = 'Generic skincare products photographed for Aspect Trading development demo' },
    @{ Key = 'automotive'; Photo = 'photo-1492144534655-ae79c964c9d7'; Title = 'Development demo automotive'; Alt = 'Generic car photographed for Aspect Trading development demo' },
    @{ Key = 'baby'; Photo = 'photo-1519238263530-99bdd11df2ea'; Title = 'Development demo baby products'; Alt = 'Generic baby clothing photographed for Aspect Trading development demo' },
    @{ Key = 'audio-detail'; Photo = 'photo-1546435770-a3e426bf472b'; Title = 'Development demo headphone detail'; Alt = 'Alternate headphones photographed for Aspect Trading development demo' }
)

$productImages = @{
    'everyday-android-smartphone-with-128-gb-storage-and-dual-sim-support' = 'phones'
    'usb-c-fast-charger-45-w' = 'phones'
    'protective-smartphone-case' = 'phones'
    'tablet-stand' = 'phones'
    'portable-ssd-1-tb' = 'computing'
    'wireless-mouse' = 'computing'
    'mechanical-keyboard' = 'computing'
    'laptop-sleeve-14-inch' = 'computing'
    'folding-laptop-stand' = 'computing'
    'dual-band-wi-fi-router' = 'computing'
    'compact-bluetooth-speaker' = 'audio'
    'wireless-noise-cancelling-headphones' = 'audio'
    'compact-usb-microphone' = 'audio'
    'cotton-crew-t-shirt' = 'fashion'
    'lightweight-everyday-backpack' = 'fashion'
    'everyday-cotton-polo-shirt' = 'fashion'
    'classic-unisex-cotton-hoodie' = 'fashion'
    'softshell-travel-organizer' = 'fashion'
    'stainless-steel-water-bottle' = 'sports'
    'resistance-band-set' = 'sports'
    'running-shoe-everyday-training-series' = 'sports'
    'lightweight-training-jacket' = 'sports'
    'adjustable-desk-lamp' = 'home-office'
    'ergonomic-office-chair' = 'home-office'
    'reusable-glass-food-storage-set' = 'home-office'
    'ceramic-tableware-set' = 'home-office'
    'digital-kitchen-scale' = 'appliances'
    'compact-personal-blender' = 'appliances'
    'rechargeable-desk-fan' = 'appliances'
    'gentle-daily-facial-cleanser' = 'beauty'
    'universal-car-phone-mount' = 'automotive'
    'all-weather-vehicle-organizer' = 'automotive'
    'soft-cotton-baby-blanket' = 'baby'
}

if (-not (Test-Path $siteRoot)) { throw 'Dedicated Aspect Trading WordPress site was not found.' }
if (-not (Test-Path $wpCli)) { throw 'WP-CLI PHAR is not available in the temporary directory.' }
if (-not (Test-Path $downloadRoot)) { New-Item -ItemType Directory -Path $downloadRoot | Out-Null }

$attachmentIds = @{}
foreach ($image in $images) {
    $file = Join-Path $downloadRoot ($image.Key + '.jpg')
    if (-not (Test-Path $file)) {
        $url = 'https://images.unsplash.com/' + $image.Photo + '?w=900&auto=format&fit=crop&q=78'
        Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 60 -OutFile $file
    }

    $importOutput = & $php $wpCli "--path=$siteRoot" "--url=$siteUrl" '--skip-themes' '--quiet' 'media' 'import' $file "--title=$($image.Title)" "--alt=$($image.Alt)" '--porcelain' 2>&1
    if ($LASTEXITCODE -ne 0) { throw "Could not import development image '$($image.Key)'." }
    $idLine = $importOutput | Where-Object { $_ -match '^\d+$' } | Select-Object -Last 1
    if (-not $idLine) { throw "WordPress did not return an attachment ID for '$($image.Key)'." }
    $attachmentIds[$image.Key] = [int]$idLine
}

$assigned = 0
foreach ($slug in $productImages.Keys) {
    $lookup = & $php $wpCli "--path=$siteRoot" "--url=$siteUrl" '--skip-themes' '--quiet' 'post' 'list' '--post_type=product' "--name=$slug" '--field=ID' 2>$null
    $productIdLine = $lookup | Where-Object { $_ -match '^\d+$' } | Select-Object -Last 1
    if (-not $productIdLine) { continue }
    $productId = [int]$productIdLine
    & $php $wpCli "--path=$siteRoot" "--url=$siteUrl" '--skip-themes' '--quiet' 'post' 'meta' 'update' $productId '_thumbnail_id' $attachmentIds[$productImages[$slug]] 2>$null | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "Could not assign image to product '$slug'." }
    $assigned++
}

$headphonesLookup = & $php $wpCli "--path=$siteRoot" "--url=$siteUrl" '--skip-themes' '--quiet' 'post' 'list' '--post_type=product' '--name=wireless-noise-cancelling-headphones' '--field=ID' 2>$null
$headphonesIdLine = $headphonesLookup | Where-Object { $_ -match '^\d+$' } | Select-Object -Last 1
if ($headphonesIdLine) {
    $headphonesId = [int]$headphonesIdLine
    & $php $wpCli "--path=$siteRoot" "--url=$siteUrl" '--skip-themes' '--quiet' 'post' 'meta' 'update' $headphonesId '_product_image_gallery' ([string]$attachmentIds['audio-detail']) 2>$null | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'Could not assign the alternate headphones gallery image.' }
}

Write-Output ('Imported image attachments: ' + $attachmentIds.Count)
Write-Output ('Demo products with featured images: ' + $assigned)
Write-Output 'Wireless headphones has a second gallery image.'
