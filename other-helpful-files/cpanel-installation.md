Open the files in a plain-text editor and use this mapping (`private.pen` should be **`private.pem`**):

| cPanel field | What to select or paste | What it is |
|---|---|---|
| **Domain** | Select the domain you generated the certificate for, e.g. `ssltest.adilarham.com`. | The website being secured. |
| **Certificate (CRT)** | The entire contents of `certificate.crt`. | Identifies your domain and contains its public key. |
| **Private Key (KEY)** | The entire contents of `private.pem`. | The secret key matching your certificate. Keep it confidential. |
| **Certificate Authority Bundle (CABUNDLE)** | The entire contents of `cabundle.crt`. | Intermediate certificates that help browsers verify your certificate. |

**Include the `-----BEGIN …-----` and `-----END …-----` lines**, along with everything between them. Paste the file contents, not the filenames or paths. Then click **Install Certificate**. [cPanel instructions](https://docs.cpanel.net/cpanel/security/ssl-tls/)

Newly generated `certificate.crt` files contain only the domain certificate. If you generated files before this version, use only the first certificate block from the older `certificate.crt`, or generate a fresh set.

Use all three files from the **same generation**, and generate in **Live** mode for a working public website. Staging certificates are for testing and won’t be trusted by browsers.


## For the staging CABUNDLE, the order is:

```text
[Existing contents of cabundle.crt]
[Matching staging root certificate downloaded from Let’s Encrypt]
```

The root is a **separate certificate**, not the first block of `certificate.crt`.

Paste your `cabundle.crt` contents to AI and It can identify which root you need.