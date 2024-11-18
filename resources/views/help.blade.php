@extends('layouts.app')

@section('title', 'Help Center')

@section('content')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <b>Help Center</b>
                </div>
                <div class="card-body text-center">
                    <a href="https://api.whatsapp.com/send/?phone=%2B6281247189174&text&type=phone_number&app_absent=0"
                        target="_blank">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode('https://api.whatsapp.com/send/?phone=%2B6281247189174&text&type=phone_number&app_absent=0') }}&size=200x200"
                            alt="QR Code" />
                    </a>
                    <div class="d-block mt-3">
                        <a href="https://api.whatsapp.com/send/?phone=%2B6281247189174&text&type=phone_number&app_absent=0"
                            class="d-block mb-5 text-dark fw-bold" target="_blank">Rahmat IT</a>
                        <p>Scan or click the QR code to call our IT Service.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
