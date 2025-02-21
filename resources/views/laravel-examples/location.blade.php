@extends('layouts.user_type.auth')

@section('content')
    <div class="container-fluid py-4">

        <!-- BAŞARILI VE HATA MESAJLARI -->
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <!-- LOKASYON LİSTESİ -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Lokasyon Listesi</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                    Lokasyon Ekle
                </button>
            </div>
            <div class="card-body">
                <table class="table align-items-center">
                    <thead>
                        <tr>
                            <th>Lokasyon Adı</th>
                            <th>Lokasyon Adresi (lat,lng)</th>
                            <th>Ekleyen Kişi</th>
                            <th>Kullanıcılar (ID’ler)</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($locations as $loc)
                            <tr>
                                <td>{{ $loc->location_name }}</td>
                                <td>{{ $loc->location_address }}</td>
                                <td>{{ $loc->created_by }}</td>
                                <td>{{ $loc->users_id }}</td>
                                <td>
                                    <!-- Düzenle Butonu -->
                                    <button class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#editLocationModal-{{ $loc->id }}">
                                        Düzenle
                                    </button>

                                    <!-- Sil Butonu -->
                                    <button class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#deleteLocationModal-{{ $loc->id }}">
                                        Sil
                                    </button>

                                    <!-- "Bu Konumdaki Kullanıcıları Göster" (opsiyonel) -->
                                    <button class="btn btn-info btn-sm"
                                        onclick="showUsersInLocation('{{ $loc->id }}')">
                                        Bu Konumdaki Kullanıcılar
                                    </button>
                                </td>
                            </tr>

                            <!-- Lokasyon Düzenleme Modalı -->
                            <div class="modal fade" id="editLocationModal-{{ $loc->id }}" tabindex="-1"
                                aria-labelledby="editLocationModalLabel-{{ $loc->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form action="{{ route('location_add.updateLocation', $loc->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editLocationModalLabel-{{ $loc->id }}">
                                                    Lokasyon Düzenle
                                                </h5>
                                                <button type="button" class="btn-close"
                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Lokasyon Adı</label>
                                                    <input type="text" class="form-control"
                                                        name="location_name" value="{{ $loc->location_name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Lokasyon Adresi (lat,lng)</label>
                                                    <!-- Konum Seç Butonu -->
                                                    <div class="input-group">
                                                        <input type="text" class="form-control"
                                                            id="location_address_{{ $loc->id }}"
                                                            name="location_address"
                                                            value="{{ $loc->location_address }}">
                                                        <button type="button" class="btn btn-info"
                                                            onclick="openMapModal('location_address_{{ $loc->id }}')">
                                                            Konum Seç
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Ekleyen Kişi</label>
                                                    <input type="text" class="form-control"
                                                        name="created_by" value="{{ $loc->created_by }}">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Kapat</button>
                                                <button type="submit" class="btn btn-primary">Güncelle</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Lokasyon Silme Modalı -->
                            <div class="modal fade" id="deleteLocationModal-{{ $loc->id }}" tabindex="-1"
                                aria-labelledby="deleteLocationModalLabel-{{ $loc->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form action="{{ route('location_add.deleteLocation', $loc->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteLocationModalLabel-{{ $loc->id }}">
                                                    Lokasyon Sil
                                                </h5>
                                                <button type="button" class="btn-close"
                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>
                                                    {{ $loc->location_name }} lokasyonunu silmek istediğinize emin misiniz?
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Kapat</button>
                                                <button type="submit" class="btn btn-danger">Sil</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- LOKASYON EKLE MODALI -->
        <div class="modal fade" id="addLocationModal" tabindex="-1" aria-labelledby="addLocationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('location_add.storeLocation') }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addLocationModalLabel">Lokasyon Ekle</h5>
                            <button type="button" class="btn-close"
                                data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Lokasyon Adı</label>
                                <input type="text" class="form-control"
                                    name="location_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Lokasyon Adresi (lat,lng)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control"
                                        id="location_address_add"
                                        name="location_address">
                                    <button type="button" class="btn btn-info"
                                        onclick="openMapModal('location_address_add')">
                                        Konum Seç
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ekleyen Kişi (opsiyonel)</label>
                                <input type="text" class="form-control"
                                    name="created_by">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">Kapat</button>
                            <button type="submit" class="btn btn-success">Ekle</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <!-- KULLANICIYA KONUM ATAMA KISMI (Senaryolar) -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Konum Atama / Güncelleme</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('location_add.assignLocationToUsers') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="scenario" class="form-label">İşlem Seçiniz</label>
                        <select name="scenario" id="scenario" class="form-control">
                            <option value="1">Yeni Kullanıcı İçin Konum (Giriş/Çıkış)</option>
                            <option value="4">Kullanıcı Çıkış Yeri Değiş (Süresiz)</option>
                            <option value="5">Kullanıcı Giriş Yeri Değiş (Süresiz)</option>
                            <option value="6">Yeni Görev Yeri Akşam 17:00 Sonra Atanır</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="location_id" class="form-label">Lokasyon Seçiniz</label>
                        <select name="location_id" id="location_id" class="form-control">
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->location_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kullanıcı çoklu seçimi -->
                    <div class="mb-3">
                        <label for="selected_users" class="form-label">Kullanıcı(lar) Seçiniz</label>
                        <select name="selected_users[]" id="selected_users" class="form-control" multiple>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Uygula</button>
                </form>
            </div>
        </div>

    </div>

    <!-- Modal: "Bu Konumdaki Kullanıcılar" (opsiyonel) -->
    <div class="modal fade" id="usersInLocationModal" tabindex="-1" aria-labelledby="usersInLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="usersInLocationModalLabel">Konumdaki Kullanıcılar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <ul id="usersInLocationList"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Google Maps Modal -->
    <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konum Seçimi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <div id="mapModalContainer" style="width:100%;height:500px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Kapat
                    </button>
                    <button type="button" class="btn btn-primary" onclick="selectLocation()">
                        Seç
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
// "Bu Konumdaki Kullanıcıları Göster" butonuna basınca (opsiyonel)
function showUsersInLocation(locationId) {
    fetch("{{ route('location_add.showUsersInLocation') }}?location_id=" + locationId)
        .then(response => response.json())
        .then(data => {
            let list = document.getElementById('usersInLocationList');
            list.innerHTML = '';
            data.forEach(user => {
                let li = document.createElement('li');
                li.textContent = user.name + " (ID: " + user.id + ")";
                list.appendChild(li);
            });
            var myModal = new bootstrap.Modal(document.getElementById('usersInLocationModal'), {keyboard: false});
            myModal.show();
        })
        .catch(err => console.error(err));
}

/* GOOGLE MAPS KONUM SEÇME MANTIĞI */
var map;
var marker;
var selectedField = null;
var mapsLoaded = false;

function initMap() {
    console.log("Google Maps API loaded.");
    mapsLoaded = true;
}

function openMapModal(field) {
    if (!mapsLoaded) {
        alert("Harita henüz yüklenmedi. Lütfen sayfayı yenileyin veya biraz bekleyin.");
        return;
    }
    selectedField = field;

    var val = document.getElementById(field).value;
    var lat = 37.13319;
    var lng = 38.740342;

    // Check if val is in "lat,lng" format
    if (val && val.includes(',')) {
        var parts = val.split(',');
        if (parts.length === 2) {
            let tmpLat = parseFloat(parts[0]);
            let tmpLng = parseFloat(parts[1]);
            // isNaN kontrolü
            if (!isNaN(tmpLat)) lat = tmpLat;
            if (!isNaN(tmpLng)) lng = tmpLng;
        }
    }

    var mapModalEl = document.getElementById('mapModal');
    var myModal = new bootstrap.Modal(mapModalEl, { keyboard: false });
    myModal.show();

    // Modal tamamen açılınca haritayı init edelim
    mapModalEl.addEventListener('shown.bs.modal', function handler() {
        mapModalEl.removeEventListener('shown.bs.modal', handler);
        initModalMap(lat, lng);
    });
}

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

    // Haritaya tıklayınca marker konumunu güncelle
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

function selectLocation() {
    if (marker && selectedField) {
        var pos = marker.getPosition();
        var lat = pos.lat().toFixed(6);
        var lng = pos.lng().toFixed(6);
        document.getElementById(selectedField).value = lat + "," + lng;
    }
    var myModalEl = document.getElementById('mapModal');
    var modal = bootstrap.Modal.getInstance(myModalEl);
    modal.hide();
}
</script>

<!-- Kendi API key’inizi buraya ekleyin -->
<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA0Sg5IHsU2_xejwA5VUGshWdhkf9EU__E&libraries=places&callback=initMap"
    async defer>
</script>
@endpush
