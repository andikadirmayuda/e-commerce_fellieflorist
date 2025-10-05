<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Pesanan - Fellie Florist</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet" />
    <link href="{{ asset('css/output.css') }}" rel="stylesheet">
</head>

<body class="min-h-screen gradient-bg font-sans">
    <div class="container mx-auto px-4 py-8 max-w-xl">
        <div class="bg-white rounded-2xl shadow-lg border border-rose-100 p-8 text-center">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">
                Pembayaran Pesanan
            </h1>
            <p class="text-gray-600 mb-6">
                Silakan lanjutkan pembayaran pesanan Anda dengan menekan tombol di bawah ini.
            </p>
            <div class="mb-6">
                <span class="block text-lg font-semibold text-rose-600 mb-2">Total Pembayaran:</span>
                <span class="block text-2xl font-bold text-gray-900 mb-2">Rp
                    {{ number_format($order->total, 0, ',', '.') }}</span>
                <span class="block text-sm text-gray-500">Order ID: {{ $order->order_id }}</span>
            </div>
            @if($order->payment_status !== 'paid')
                <button type="button" onclick="payWithMidtrans()"
                    class="px-6 py-3 bg-gradient-to-r from-rose-500 to-pink-500 text-white font-semibold rounded-xl shadow-lg hover:from-rose-600 hover:to-pink-700 transition-all duration-200">
                    <i class="bi bi-credit-card mr-2"></i> Bayar Sekarang!
                </button>
            @endif

            @if($order->payment_status === 'paid')
                <a id="order-detail-btn" href="/order/{{ $order->public_code }}" style="margin-top:2rem;"
                    class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-xl shadow-lg hover:from-emerald-600 hover:to-green-700 transition-all duration-200">
                    <i class="bi bi-file-earmark-text mr-2"></i> Lihat Detail Order
                </a>
            @else
                <a id="order-detail-btn" href="/order/{{ $order->public_code }}" style="display:none; margin-top:2rem;"
                    class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-xl shadow-lg hover:from-emerald-600 hover:to-green-700 transition-all duration-200">
                    <i class="bi bi-file-earmark-text mr-2"></i> Lihat Detail Order
                </a>
            @endif
        </div>
    </div>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    <script>
        function payWithMidtrans() {
            fetch("{{ route('payment.snap_token') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify({
                    name: "{{ $order->customer_name }}",
                    email: "{{ $order->customer_email }}",
                    phone: "{{ $order->wa_number }}",
                    amount: {{ $order->total }}
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.snap_token) {
                        window.snap.pay(data.snap_token, {
                            onSuccess: function (result) {
                                alert('Pembayaran berhasil!');
                                document.getElementById('order-detail-btn').style.display = 'inline-block';
                            },
                            onPending: function (result) {
                                alert('Pembayaran pending!');
                            },
                            onError: function (result) {
                                alert('Pembayaran gagal!');
                            },
                            onClose: function () {
                                alert('Anda menutup popup tanpa menyelesaikan pembayaran');
                            }
                        });
                    } else {
                        alert('Gagal mendapatkan token pembayaran: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    alert('Terjadi kesalahan: ' + error);
                });
        }
    </script>
</body>

</html>