$body = '{"event":"charge.success","data":{"reference":"test_ref_002","amount":500000,"currency":"NGN"}}'
$secretKey = "sk_test_2bf84c1dc166da793716e211264661d27c8ab28d"  

# Write the body to an actual file, untouched
Set-Content -Path "webhook-payload.json" -Value $body -NoNewline -Encoding ascii

$hmac = New-Object System.Security.Cryptography.HMACSHA512
$hmac.Key = [System.Text.Encoding]::UTF8.GetBytes($secretKey)
$hashBytes = $hmac.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($body))
$signature = [System.BitConverter]::ToString($hashBytes).Replace("-", "").ToLower()

Write-Host "Calculated signature: $signature"

# --data-binary reads the file's raw bytes exactly as-is - no shell
# quote-mangling in between, since curl reads straight from disk.
curl.exe -X POST "http://selar-paytrace.test/webhooks/paystack" `
  -H "Content-Type: application/json" `
  -H "x-paystack-signature: $signature" `
  --data-binary "@webhook-payload.json"