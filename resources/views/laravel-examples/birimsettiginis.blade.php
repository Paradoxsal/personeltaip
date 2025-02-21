@extends('layouts.user_type.auth')

@section('content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                        <h6>Birimler</h6>
                        <!-- Birim Ekle Butonu -->
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addBirimModal">
                            Birim Ekle
                        </button>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">

                        <!-- Başarı mesajı gösterme -->
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <!-- Hata mesajı gösterme -->
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0" id="departmanTable">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Birim Adı
                                        </th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Birim Başkanı
                                        </th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Birim Konumu
                                        </th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            İşlemler
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($departmanlar as $departman)
                                        <tr>
                                            <td>{{ $departman->unit_name }}</td>
                                            <td>{{ $departman->unit_head }}</td>
                                            <td class="text-center">{{ $departman->unit_location }}</td>
                                            <td class="text-center">
                                                <!-- Düzenleme Modalını tetikle -->
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#editBirimModal-{{ $departman->id }}">
                                                    Edit
                                                </button>

                                                <!-- Silme Modalını tetikle -->
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#deleteBirimModal-{{ $departman->id }}">
                                                    Sil
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Düzenleme Modalı -->
                                        <div class="modal fade" id="editBirimModal-{{ $departman->id }}" tabindex="-1"
                                            aria-labelledby="editBirimModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editBirimModalLabel">
                                                            Birim Düzenle
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('birimsettiginis.update', $departman->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="unit_name" class="form-control-label">
                                                                    Birim Adı
                                                                </label>
                                                                <input type="text" class="form-control"
                                                                    name="unit_name"
                                                                    value="{{ $departman->unit_name }}"
                                                                    required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="unit_head" class="form-control-label">
                                                                    Birim Başkanı
                                                                </label>
                                                                <input type="text" class="form-control"
                                                                    name="unit_head"
                                                                    value="{{ $departman->unit_head }}"
                                                                    required>
                                                            </div>

                                                            <!-- Birim Konumu + Harita ile Seçme -->
                                                            <div class="mb-3">
                                                                <label for="unit_location" class="form-control-label">
                                                                    Birim Konumu
                                                                </label>
                                                                <div class="input-group">
                                                                    <!-- ID'sine dikkat! Her kayıt için farklı ID -->
                                                                    <input type="text" class="form-control"
                                                                        id="unit_location_{{ $departman->id }}"
                                                                        name="unit_location"
                                                                        value="{{ $departman->unit_location }}"
                                                                        required>
                                                                    <!-- "Konum Seç" Butonu -->
                                                                    <button type="button" class="btn btn-info"
                                                                        onclick="openMapModal('unit_location_{{ $departman->id }}')">
                                                                        Konum Seç
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">
                                                                Kapat
                                                            </button>
                                                            <button type="submit" class="btn btn-primary">
                                                                Güncelle
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Silme Modalı -->
                                        <div class="modal fade" id="deleteBirimModal-{{ $departman->id }}" tabindex="-1"
                                            aria-labelledby="deleteBirimModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteBirimModalLabel">
                                                            Birim Sil
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>
                                                            {{ $departman->unit_name }} birimini silmek istediğinizden emin
                                                            misiniz?
                                                        </p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">
                                                            Kapat
                                                        </button>
                                                        <form action="{{ route('birimsettiginis.destroy', $departman->id) }}"
                                                            method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">
                                                                Sil
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Birim Ekleme Modalı -->
    <div class="modal fade" id="addBirimModal" tabindex="-1" aria-labelledby="addBirimModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBirimModalLabel">Birim Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('birimsettiginis.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="unit_name" class="form-control-label">
                                Birim Adı
                            </label>
                            <input type="text" class="form-control" name="unit_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="unit_head" class="form-control-label">
                                Birim Başkanı
                            </label>
                            <input type="text" class="form-control" name="unit_head" required>
                        </div>
                        <div class="mb-3">
                            <label for="unit_location" class="form-control-label">
                                Birim Konumu
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control"
                                    id="unit_location_add"
                                    name="unit_location" required>
                                <!-- Konum Seç butonu -->
                                <button type="button" class="btn btn-info"
                                    onclick="openMapModal('unit_location_add')">
                                    Konum Seç
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Kapat
                        </button>
                        <button type="submit" class="btn btn-success">
                            Ekle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Google Maps Modal (Tek bir modal, tüm konum seçimleri için kullanıyoruz) -->
    <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Konum Seçimi') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <div id="mapModalContainer" style="width:100%;height:500px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('Kapat') }}
                    </button>
                    <button type="button" class="btn btn-primary" onclick="selectLocation()">
                        {{ __('Seç') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        // Google Maps değişkenleri
        var map;
        var marker;
        var selectedField = null;
        var mapsLoaded = false;

        // Google Maps API yüklendiğinde çağrılacak callback
        function initMap() {
            console.log("Google Maps API loaded.");
            mapsLoaded = true;
        }

        // "Konum Seç" butonuna basıldığında çalışır
        function openMapModal(field) {
            if (!mapsLoaded) {
                alert("Harita henüz yüklenmedi. Lütfen sayfayı yenileyin veya biraz bekleyin.");
                return;
            }
            selectedField = field;

            // Mevcut lat,lng değerini inputtan çek
            var val = document.getElementById(field).value;
            var lat = 37.13319;   // Varsayılan
            var lng = 38.740342; // Varsayılan
            if (val && val.includes(',')) {
                var parts = val.split(',');
                lat = parseFloat(parts[0]);
                lng = parseFloat(parts[1]);
            }

            // Modal'ı aç
            var mapModalEl = document.getElementById('mapModal');
            var myModal = new bootstrap.Modal(mapModalEl, { keyboard: false });
            myModal.show();

            // Modal tamamen gösterildiğinde haritayı init edelim
            mapModalEl.addEventListener('shown.bs.modal', function handler() {
                mapModalEl.removeEventListener('shown.bs.modal', handler);
                initModalMap(lat, lng);
            });
        }

        // Haritayı oluşturma
        function initModalMap(lat, lng) {
            console.log("initModalMap called with lat:", lat, "lng:", lng);
            var center = { lat: lat, lng: lng };

            map = new google.maps.Map(document.getElementById("mapModalContainer"), {
                zoom: 14,
                center: center
            });

            marker = new google.maps.Marker({
                position: center,
                map: map,
                draggable: false
            });

            // Haritaya tıklanınca marker konumunu güncelle
            map.addListener('click', function(e) {
                placeMarker(e.latLng, map);
            });
        }

        function placeMarker(location, map) {
            if (marker) {
                marker.setPosition(location);
            } else {
                marker = new google.maps.Marker({
                    position: location,
                    map: map
                });
            }
        }

        // "Seç" butonuna basınca input'a lat,lng yaz
        function selectLocation() {
            if (marker && selectedField) {
                var pos = marker.getPosition();
                var lat = pos.lat().toFixed(6);
                var lng = pos.lng().toFixed(6);
                document.getElementById(selectedField).value = lat + "," + lng;
            }
            // Modal'ı kapat
            var myModalEl = document.getElementById('mapModal');
            var modal = bootstrap.Modal.getInstance(myModalEl);
            modal.hide();
        }
    </script>

    <!-- Kendi API keyinizi burada "YOUR_API_KEY" ile değiştirin -->
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA0Sg5IHsU2_xejwA5VUGshWdhkf9EU__E&libraries=places&callback=initMap"
        async defer>
    </script>
@endpush
