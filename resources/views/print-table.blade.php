<!doctype html>
<html lang="{{ $locale }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "IBM Plex Sans Arabic", "Segoe UI", Tahoma, Arial, sans-serif; font-size: 10pt; color: #111; }
        header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8mm; }
        h1 { margin: 0; font-size: 15pt; }
        .meta { color: #555; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: start; vertical-align: top; }
        th { background: #f2f2f2; font-weight: 600; }
        tbody tr:nth-child(even) td { background: #fafafa; }
        .empty { text-align: center; color: #555; padding: 12px; }
        @media print { th, tbody tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
    <header>
        <h1>{{ $title }}</h1>
        <span class="meta">{{ __('filament-conventions::exports.printed_at', ['date' => $printedAt]) }}</span>
    </header>
    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php($count = 0)
            @foreach ($rows as $row)
                @php($count++)
                <tr>
                    @foreach ($row as $cell)
                        <td><bdi>{{ $cell }}</bdi></td>
                    @endforeach
                </tr>
            @endforeach
            @if ($count === 0)
                <tr><td class="empty" colspan="{{ max(count($headers), 1) }}">{{ __('filament-conventions::exports.empty') }}</td></tr>
            @endif
        </tbody>
    </table>
    <p class="meta">{{ __('filament-conventions::exports.rows_count', ['count' => $count]) }}</p>
</body>
</html>
