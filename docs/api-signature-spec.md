# Dixlase API Signature Specification v1

## Overview

This document defines the HMAC-SHA256 signature specification for Dixlase API and Webhook authentication. All external API requests and Webhook deliveries MUST follow this specification to ensure message integrity and authenticity.

## Version

- **Specification Version**: v1
- **Algorithm**: HMAC-SHA256
- **Status**: Stable

## 1. Signature Components

### 1.1 Required HTTP Headers

| Header Name | Description | Example |
|-------------|-------------|---------|
| `X-Dixlase-Key` | API key identifier | `dls_key_abc123...` |
| `X-Dixlase-Timestamp` | Unix timestamp (seconds) | `1733500800` |
| `X-Dixlase-Signature` | Signature with version prefix | `v1=a1b2c3d4...` |

### 1.2 Signing String Format

The signing string is constructed by joining the following components with newline (`\n`) characters:

```
{timestamp}\n{http_method}\n{path}\n{body_hash}
```

| Component | Description | Example |
|-----------|-------------|---------|
| `timestamp` | Unix timestamp (same as header) | `1733500800` |
| `http_method` | HTTP method in UPPERCASE | `POST` |
| `path` | Request path without query string | `/api/v1/orders` |
| `body_hash` | SHA-256 hash of raw request body (hex) | `e3b0c44298fc...` |

### 1.3 Body Hash Rules

- **Empty body**: Use SHA-256 hash of empty string (`e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`)
- **Non-empty body**: Use SHA-256 hash of raw request body as hexadecimal string
- **Encoding**: Body must be hashed as-is (raw bytes), not URL-encoded

## 2. Signature Generation

### 2.1 Algorithm

```
signature = HMAC-SHA256(secret, signing_string)
```

### 2.2 Example (PHP)

```php
$timestamp = time();
$method = 'POST';
$path = '/api/v1/orders';
$body = '{"order_id": 123}';
$secret = 'your_api_secret';

// Step 1: Create body hash
$bodyHash = hash('sha256', $body);

// Step 2: Build signing string
$signingString = implode("\n", [
    $timestamp,
    strtoupper($method),
    $path,
    $bodyHash,
]);

// Step 3: Generate signature
$signature = hash_hmac('sha256', $signingString, $secret);

// Step 4: Set headers
$headers = [
    'X-Dixlase-Key' => $apiKey,
    'X-Dixlase-Timestamp' => $timestamp,
    'X-Dixlase-Signature' => 'v1=' . $signature,
];
```

### 2.3 Example Signing String

```
1733500800
POST
/api/v1/orders
bf718b6f653bebc184e1479f1935b8da974d701b893afcf49e701f3e2f9f9c5a
```

## 3. Signature Verification

### 3.1 Verification Steps

1. **Extract headers**: Get `X-Dixlase-Key`, `X-Dixlase-Timestamp`, `X-Dixlase-Signature`
2. **Validate timestamp**: Reject if timestamp differs from server time by more than ±300 seconds (5 minutes)
3. **Lookup secret**: Find API client by key and retrieve secret
4. **Parse signature**: Extract version and signature value from `v1=xxxxx` format
5. **Rebuild signing string**: Using same rules as sender
6. **Compute expected signature**: `HMAC-SHA256(secret, signing_string)`
7. **Compare signatures**: Use constant-time comparison (`hash_equals`)

### 3.2 Error Responses

| HTTP Status | Error Code | Description |
|-------------|------------|-------------|
| 401 | `missing_headers` | Required signature headers are missing |
| 401 | `timestamp_expired` | Timestamp is outside acceptable range |
| 401 | `invalid_api_key` | API key not found or revoked |
| 401 | `unsupported_version` | Signature version not supported |
| 401 | `invalid_signature` | Signature verification failed |

### 3.3 Error Response Format

```json
{
    "error": {
        "code": "invalid_signature",
        "message": "Signature verification failed"
    }
}
```

## 4. Security Considerations

### 4.1 Timestamp Tolerance

- Default tolerance: ±300 seconds (5 minutes)
- This prevents replay attacks while allowing for clock skew
- Servers SHOULD use NTP to maintain accurate time

### 4.2 Replay Attack Prevention

For high-security scenarios, servers MAY implement additional replay protection:

- Store `(timestamp, signature)` pairs temporarily
- Reject requests with previously seen combinations
- Cache duration should match timestamp tolerance

### 4.3 Secret Management

- Secrets MUST be at least 32 characters (256 bits)
- Secrets SHOULD be generated using cryptographically secure random generators
- Secrets MUST be stored encrypted or hashed in the database
- Secrets MUST NOT be logged or exposed in error messages

### 4.4 Constant-Time Comparison

Always use constant-time comparison for signature verification to prevent timing attacks:

```php
// Correct
hash_equals($expected, $actual);

// Incorrect (vulnerable to timing attacks)
$expected === $actual;
```

## 5. Webhook Signatures

When Dixlase sends webhooks to external systems, the same signature format is used:

### 5.1 Webhook Headers

```
X-Dixlase-Timestamp: 1733500800
X-Dixlase-Signature: v1=a1b2c3d4e5f6...
```

Note: `X-Dixlase-Key` is not included in webhook requests as the recipient already knows which Dixlase instance is sending.

### 5.2 Webhook Verification (Recipient Side)

Recipients should:

1. Store the webhook secret provided during registration
2. Verify the signature using the same algorithm
3. Reject requests with invalid or expired signatures

## 6. Version Migration

### 6.1 Version Format

Signatures include a version prefix: `v1=signature_hex`

### 6.2 Future Versions

If specification changes are needed:

1. New version (e.g., `v2`) will be introduced
2. Both versions will be supported during transition period
3. Deprecation notice will be provided at least 6 months in advance
4. Old version will be disabled after migration period

### 6.3 Multiple Version Support

Servers MAY accept multiple signature versions:

```
X-Dixlase-Signature: v1=abc123,v2=def456
```

## 7. API Key Format

### 7.1 Key Structure

```
dls_{type}_{random}
```

| Component | Description | Example |
|-----------|-------------|---------|
| `dls` | Dixlase prefix | `dls` |
| `type` | Key type | `key`, `wh` (webhook) |
| `random` | Random identifier (32 chars) | `abc123def456...` |

### 7.2 Examples

- API Key: `dls_key_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`
- Webhook Key: `dls_wh_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`

## 8. Implementation Checklist

### For API Clients

- [ ] Generate timestamp at request time
- [ ] Build signing string with correct format
- [ ] Compute HMAC-SHA256 signature
- [ ] Include all required headers
- [ ] Handle 401 errors appropriately

### For API Servers (Dixlase)

- [ ] Validate all required headers present
- [ ] Check timestamp within tolerance
- [ ] Lookup API key and retrieve secret
- [ ] Verify signature with constant-time comparison
- [ ] Log failed verification attempts
- [ ] Return appropriate error codes

### For Webhook Recipients

- [ ] Store webhook secret securely
- [ ] Verify signature on all incoming webhooks
- [ ] Check timestamp freshness
- [ ] Return 200 OK only after successful verification

## Appendix A: Test Vectors

### Test Case 1: Basic POST Request

**Input:**
- Timestamp: `1733500800`
- Method: `POST`
- Path: `/api/v1/orders`
- Body: `{"order_id":123}`
- Secret: `test_secret_key_32_characters_xx`

**Expected:**
- Body Hash: `bf718b6f653bebc184e1479f1935b8da974d701b893afcf49e701f3e2f9f9c5a`
- Signing String:
  ```
  1733500800
  POST
  /api/v1/orders
  bf718b6f653bebc184e1479f1935b8da974d701b893afcf49e701f3e2f9f9c5a
  ```
- Signature: `v1=...` (compute with your implementation)

### Test Case 2: GET Request (Empty Body)

**Input:**
- Timestamp: `1733500800`
- Method: `GET`
- Path: `/api/v1/users`
- Body: (empty)
- Secret: `test_secret_key_32_characters_xx`

**Expected:**
- Body Hash: `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`

## Appendix B: Language Examples

### JavaScript (Node.js)

```javascript
const crypto = require('crypto');

function signRequest(method, path, body, secret, timestamp = null) {
    timestamp = timestamp || Math.floor(Date.now() / 1000);
    const bodyHash = crypto.createHash('sha256').update(body || '').digest('hex');
    
    const signingString = [
        timestamp,
        method.toUpperCase(),
        path,
        bodyHash
    ].join('\n');
    
    const signature = crypto.createHmac('sha256', secret)
        .update(signingString)
        .digest('hex');
    
    return {
        'X-Dixlase-Timestamp': timestamp.toString(),
        'X-Dixlase-Signature': `v1=${signature}`
    };
}
```

### Python

```python
import hmac
import hashlib
import time

def sign_request(method, path, body, secret, timestamp=None):
    timestamp = timestamp or int(time.time())
    body_hash = hashlib.sha256((body or '').encode()).hexdigest()
    
    signing_string = '\n'.join([
        str(timestamp),
        method.upper(),
        path,
        body_hash
    ])
    
    signature = hmac.new(
        secret.encode(),
        signing_string.encode(),
        hashlib.sha256
    ).hexdigest()
    
    return {
        'X-Dixlase-Timestamp': str(timestamp),
        'X-Dixlase-Signature': f'v1={signature}'
    }
```

---

**Document Version**: 1.0.0  
**Last Updated**: 2025-01-01  
**Author**: Dixlase Development Team
