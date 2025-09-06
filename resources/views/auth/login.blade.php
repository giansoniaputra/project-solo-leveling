<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <button id="login-faceid">Login dengan Face ID</button>
    <script>
        document.getElementById('login-faceid').onclick = async () => {
            const options = await fetch('/login/webauthn/options').then(r => r.json());
            options.challenge = base64urlToUint8Array(options.challenge);

            const assertion = await navigator.credentials.get({
                publicKey: options
            });

            await fetch('/login/webauthn', {
                method: 'POST'
                , headers: {
                    'Content-Type': 'application/json'
                }
                , body: JSON.stringify(assertion)
            }).then(r => {
                if (r.ok) {
                    window.location.href = '/';
                } else {
                    alert("Login gagal!");
                }
            });

        };

        function base64urlToUint8Array(base64url) {
            // Ubah karakter base64url ke base64 standar
            let base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
            // Tambahkan padding kalau perlu
            while (base64.length % 4) {
                base64 += '=';
            }
            const rawData = window.atob(base64);
            return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
        }

    </script>

</body>
</html>
