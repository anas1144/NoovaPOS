<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete your payment — NoovaPOS</title>
    <style>
        :root { --brand:#4f46e5; --bg:#0f172a; --card:#ffffff; --muted:#64748b; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
            background:linear-gradient(160deg,#1e1b4b,#0f172a); color:#0f172a;
            min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card { background:var(--card); width:100%; max-width:460px; border-radius:18px;
            box-shadow:0 24px 60px rgba(0,0,0,.35); overflow:hidden; }
        .head { background:var(--brand); color:#fff; padding:22px 26px; }
        .head h1 { margin:0; font-size:18px; }
        .head p { margin:6px 0 0; opacity:.85; font-size:13px; }
        .body { padding:24px 26px; }
        .amount { font-size:34px; font-weight:700; letter-spacing:-.5px; }
        .row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #eef2f7; font-size:14px; }
        .row span:first-child { color:var(--muted); }
        .channel { margin-top:18px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; }
        .channel h3 { margin:0 0 6px; font-size:15px; }
        .channel p { margin:0; color:var(--muted); font-size:13px; line-height:1.5; }
        .badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; }
        .pending { background:#fef9c3; color:#854d0e; }
        .paid { background:#dcfce7; color:#166534; }
        .rejected { background:#fee2e2; color:#991b1b; }
        .btn { display:block; text-align:center; margin-top:20px; background:var(--brand); color:#fff;
            text-decoration:none; padding:13px; border-radius:11px; font-weight:600; }
        .foot { text-align:center; color:var(--muted); font-size:12px; padding:0 26px 22px; }
        .acct { font-family:ui-monospace,monospace; background:#eef2ff; padding:2px 6px; border-radius:6px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <h1>Complete your payment</h1>
            <p>NoovaPOS subscription</p>
        </div>
        <div class="body">
            <div class="amount">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</div>
            <div style="margin:14px 0 8px;">
                @php $st = $payment->status; @endphp
                <span class="badge {{ $st === 'paid' ? 'paid' : ($st === 'rejected' ? 'rejected' : 'pending') }}">
                    {{ strtoupper($st) }}
                </span>
            </div>

            <div class="row"><span>Billing cycle</span><span>{{ ucfirst($payment->billing_cycle) }} × {{ $payment->periods }}</span></div>
            @if($payment->checkout_reference)
                <div class="row"><span>Your reference</span><span>{{ $payment->checkout_reference }}</span></div>
            @endif
            <div class="row"><span>Reference #</span><span>#{{ $payment->id }}</span></div>

            @if($channel)
                <div class="channel">
                    <h3>{{ $channel['label'] ?? 'Payment' }}</h3>
                    @if(!empty($channel['account']))
                        <p>Pay to: <span class="acct">{{ $channel['account'] }}</span></p>
                    @endif
                    @if(!empty($channel['instructions']))
                        <p>{{ $channel['instructions'] }}</p>
                    @endif
                </div>

                @if(($channel['type'] ?? '') === 'link' && !empty($channel['url']))
                    <a class="btn" href="{{ $channel['url'] }}" target="_blank" rel="noopener">Pay now</a>
                @endif
            @endif
        </div>
        <div class="foot">
            @if($payment->status === 'paid')
                Payment confirmed — your subscription is active. You can close this page.
            @elseif($payment->status === 'rejected')
                This payment was rejected. Please return to your Billing page and try again.
            @else
                After paying, our team confirms the payment and your subscription activates automatically.
            @endif
        </div>
    </div>
</body>
</html>
