<div>
    <div class="card">
        <div class="card-header">
            <b>DKS Scan</b>
            <hr>
        </div>
        <div class="card-body">
            <div id="placeholder" class="placeholder text-center">
                <p id="scan-text">Click "Start Scanning" to begin.</p>
                <div id="loading" class="text-center d-none">
                    <div class="spinner-border" role="status"></div>
                    <div>Loading...</div>
                </div>
            </div>

            <div id="reader" class="img-fluid mb-3"></div>

            <div id="result" class="mb-3"></div>

            <div class="d-grid">
                <button id="start-button" class="btn btn-success">Start Scanning</button>
                <button id="stop-button" class="btn btn-danger d-none" style="display: none;">Stop Scanning</button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="tqModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="tqModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="tqModalLabel">Pilih Tempat</h1>
                </div>
                <div class="modal-body">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="Tq" id="TQ">
                        <label class="form-check-label" for="TQ">
                            Sinar Taqwa Motor 1
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="Tq" id="TQ2" checked>
                        <label class="form-check-label" for="TQ2">
                            Sinar Taqwa Motor 2
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="confirmSelection">Konfirmasi</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const html5QrCode = new Html5Qrcode("reader");
            let scanning = false;

            function getRandomString(length) {
                const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let result = '';
                for (let i = 0; i < length; i++) {
                    result += characters.charAt(Math.floor(Math.random() * characters.length));
                }
                return result;
            }

            document.getElementById("start-button").addEventListener("click", () => {
                document.getElementById("start-button").setAttribute('disabled', 'true');

                document.getElementById("loading").classList.remove('d-none');
                document.getElementById("scan-text").classList.add('d-none');

                function getQrBoxSize() {
                    const width = window.innerWidth;
                    const height = window.innerHeight;
                    const qrBoxSize = Math.min(width, height) * 0.25;
                    return {
                        width: Math.max(qrBoxSize, 200),
                        height: Math.max(qrBoxSize, 200)
                    };
                }

                Html5Qrcode.getCameras().then(devices => {
                    if (devices && devices.length) {
                        var cameraId = devices[0].id;
                        const config = {
                            aspectRatio: 1,
                            qrbox: getQrBoxSize(),
                        };

                        const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                            let redirectUrl = '';
                            const url = new URL(decodedText);
                            const kd_toko = url.searchParams.get('kd_toko');
                            const katalog = url.searchParams.get('Katalog');

                            if (katalog) {
                                redirectUrl = `/dks-scan/${kd_toko}?katalog=${katalog}`;
                            } else {
                                redirectUrl = `/dks-scan/${kd_toko}`;
                            }

                            document.getElementById("loading").classList.remove('d-none');
                            document.getElementById("stop-button").classList.add('d-none');

                            html5QrCode.stop().then(() => {
                                document.getElementById("placeholder").classList.remove('d-none');

                                if (kd_toko == 'TQ') {
                                    $('#tqModal').modal('show');

                                    document.getElementById('confirmSelection').onclick = () => {
                                        const selectedOption = document.querySelector(
                                            'input[name="Tq"]:checked').id;

                                        $('#tqModal').modal('hide');

                                        if (katalog) {
                                            window.location.href =
                                                `/dks-scan/${selectedOption}?katalog=${katalog}`;
                                        } else {
                                            window.location.href =
                                            `/dks-scan/${selectedOption}`;
                                        }

                                    };
                                } else {
                                    window.location.href = redirectUrl;
                                }
                            });
                        };

                        html5QrCode.start({

                            facingMode: {
                                exact: "environment"
                                // exact: "user"
                            }
                        }, config, qrCodeSuccessCallback).then(() => {
                            scanning = true;
                            document.getElementById("loading").classList.add('d-none');
                            document.getElementById("start-button").removeAttribute('disabled');
                            document.getElementById("start-button").classList.add('d-none');
                            document.getElementById("stop-button").classList.remove('d-none');
                            document.getElementById("placeholder").classList.add('d-none');
                        }).catch(err => {
                            alert('Error starting scanner');
                            location.reload()
                        });
                    } else {
                        document.getElementById("result").innerText = "No camera found.";
                    }
                }).catch(err => {
                    alert('Camera access denied or not available');
                    location.reload()
                });
            });

            document.getElementById("stop-button").addEventListener("click", () => {
                if (scanning) {
                    html5QrCode.stop().then(() => {
                        scanning = false;
                        document.getElementById("scan-text").classList.remove('d-none');
                        document.getElementById("start-button").classList.remove('d-none');
                        document.getElementById("stop-button").classList.add('d-none');
                        document.getElementById("placeholder").classList.remove('d-none');
                    }).catch(err => {
                        document.getElementById("result").innerText = `Error stopping scanner: ${err}`;
                    });
                }
            });
        </script>
    @endpush
</div>
