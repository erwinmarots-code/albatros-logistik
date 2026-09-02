<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Albatros Logistik</title>
</head>
<body>
    <div id="app">
        <h1>Albatros Logistik</h1>
        <p>Jika Anda melihat ini, aplikasi Vue belum ter-load.</p>
        <p>File JS: <span id="js-status">Memeriksa...</span></p>
    </div>
    <script>
        document.getElementById('js-status').textContent = 'File JS tidak ditemukan!';
        fetch('/main-DbhuzUvh.js')
            .then(r => r.ok ? document.getElementById('js-status').textContent = '✅ File JS ditemukan!' : null)
            .catch(() => {});
    </script>
</body>
</html>
