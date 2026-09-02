<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Albatros Logistik</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    @php
        $manifestPath = public_path('assets/manifest.json');
        $manifest = [];
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
        }
        $entry = $manifest['src/main.js'] ?? null;
        $cssFile = $entry['css'][0] ?? null;
        $jsFile = $entry['file'] ?? null;
    @endphp

    @if ($cssFile)
        <link rel="stylesheet" href="{{ asset('assets/' . $cssFile) }}">
    @endif
</head>
<body>
    <div id="app"></div>
    @if ($jsFile)
        <script type="module" src="{{ asset('assets/' . $jsFile) }}"></script>
    @endif
</body>
</html>