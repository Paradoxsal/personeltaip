@extends('layouts.user_type.auth')

@section('content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                        <h6>Kullanıcılar</h6>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0" id="authorsTable">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Personel Adı
                                        </th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Personel Birimi
                                        </th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Personel Durumu
                                        </th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Personel Kayıt Tarihi
                                        </th>
                                        <th class="text-secondary opacity-7"></th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        @php
                                            // attendance tablosu kullanıyorsanız, kullanıcıya ait kaydı bulun
                                            $attendance = $attendances->firstWhere('user_id', $user->id) ?? null;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm">{{ $user->name }}</h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    // Kullanıcının birim id'sine göre birim adını çekiyoruz
$departmentName =
    $departments->firstWhere('id', $user->units_id)?->unit_name ??
    'Bilinmiyor';
                                                @endphp
                                                <p class="text-xs font-weight-bold mb-0">{{ $departmentName }}</p>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="badge badge-sm bg-gradient-success">Online</span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-secondary text-xs font-weight-bold">
                                                    {{ $user->created_at->format('d/m/y') }}
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <!-- Düzenleme için modal formu tetikle -->
                                                <button type="button" class="text-secondary font-weight-bold text-xs"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUserModal-{{ $user->id }}">
                                                    Düzenle
                                                </button>
                                            </td>
                                            <td class="align-middle">
                                                <!-- Silme için modal -->
                                                <button type="button" class="text-danger font-weight-bold text-xs"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteUserModal-{{ $user->id }}">
                                                    Sil
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Düzenleme Modalı -->
                                        <div class="modal fade" id="editUserModal-{{ $user->id }}" tabindex="-1"
                                            aria-labelledby="editUserModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editUserModalLabel">
                                                            Kullanıcı Düzenle
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('usersettiginis.update', $user->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">

                                                            <div class="mb-3">
                                                                <label class="form-control-label">
                                                                    Kullanıcı ID
                                                                </label>
                                                                <input type="text" class="form-control"
                                                                    value="{{ $user->id }}" disabled>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="name" class="form-control-label">
                                                                    Kullanıcı Adı
                                                                </label>
                                                                <input type="text" class="form-control" name="name"
                                                                    value="{{ $user->name }}" required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="email" class="form-control-label">
                                                                    Email
                                                                </label>
                                                                <input type="email" class="form-control" name="email"
                                                                    value="{{ $user->email }}" required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="phone" class="form-control-label">
                                                                    Telefon
                                                                </label>
                                                                <input type="text" class="form-control" name="phone"
                                                                    value="{{ $user->phone }}" required>
                                                            </div>

                                                            <!-- Attendance / Giriş Saati -->
                                                            @if ($attendance)
                                                                <div class="mb-3">
                                                                    <label for="check_in_time" class="form-control-label">
                                                                        Giriş Yaptığı Saat
                                                                    </label>
                                                                    <input type="datetime-local" class="form-control"
                                                                        name="check_in_time"
                                                                        @if ($attendance->check_in_time) value="{{ \Carbon\Carbon::parse($attendance->check_in_time)->format('Y-m-d\TH:i') }}" @endif>
                                                                    <small class="text-muted">
                                                                        Şu anki değer:
                                                                        {{ $attendance->check_in_time ?? 'Yok' }}
                                                                    </small>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="check_out_time" class="form-control-label">
                                                                        Çıkış Yaptığı Saat
                                                                    </label>
                                                                    <input type="datetime-local" class="form-control"
                                                                        name="check_out_time"
                                                                        @if ($attendance->check_out_time) value="{{ \Carbon\Carbon::parse($attendance->check_out_time)->format('Y-m-d\TH:i') }}" @endif>
                                                                    <small class="text-muted">
                                                                        Şu anki değer:
                                                                        {{ $attendance->check_out_time ?? 'Yok' }}
                                                                    </small>
                                                                </div>
                                                            @endif

                                                            <!-- Harita ile Konum Seçimi -->
                                                            <div class="mb-3">
                                                                <label for="check_in_location" class="form-control-label">
                                                                    Giriş Konumun
                                                                </label>
                                                                <div class="input-group">
                                                                    <input class="form-control" type="text"
                                                                        id="check_in_location_{{ $user->id }}"
                                                                        name="check_in_location"
                                                                        value="{{ $user->check_in_location }}">
                                                                    <button type="button" class="btn btn-info"
                                                                        onclick="openMapModal('check_in_location_{{ $user->id }}')">
                                                                        {{ __('Giriş Konumunu Seç') }}
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="check_out_location"
                                                                    class="form-control-label">
                                                                    Çıkış Konumun
                                                                </label>
                                                                <div class="input-group">
                                                                    <input class="form-control" type="text"
                                                                        id="check_out_location_{{ $user->id }}"
                                                                        name="check_out_location"
                                                                        value="{{ $user->check_out_location }}">
                                                                    <button type="button" class="btn btn-info"
                                                                        onclick="openMapModal('check_out_location_{{ $user->id }}')">
                                                                        {{ __('Çıkış Konumunu Seç') }}
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="password" class="form-control-label">
                                                                    Şifre Değiştir
                                                                </label>
                                                                <input type="password" class="form-control"
                                                                    name="password" value="">
                                                                <small class="text-muted">
                                                                    Boş bırakırsanız mevcut şifre korunur
                                                                </small>
                                                            </div>

                                                            <!-- Birim Seçimi -->
                                                            <div class="mb-3">
                                                                <label for="units_id" class="form-control-label">
                                                                    {{ __('Birim') }}
                                                                </label>
                                                                <select id="units_id" name="units_id"
                                                                    class="form-control">
                                                                    @foreach ($departments as $department)
                                                                        <option value="{{ $department->id }}"
                                                                            {{ $user->units_id == $department->id ? 'selected' : '' }}>
                                                                            {{ $department->unit_name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="role" class="form-control-label">
                                                                    Personel Yetkisi
                                                                </label>
                                                                <select id="role" name="role"
                                                                    class="form-control">
                                                                    <option value="1"
                                                                        {{ $user->role == 1 ? 'selected' : '' }}>
                                                                        Yetki Ver
                                                                    </option>
                                                                    <option value="0"
                                                                        {{ $user->role == 0 ? 'selected' : '' }}>
                                                                        Yetki Verme
                                                                    </option>
                                                                </select>
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
                                        <div class="modal fade" id="deleteUserModal-{{ $user->id }}" tabindex="-1"
                                            aria-labelledby="deleteUserModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteUserModalLabel">
                                                            Kullanıcıyı Sil
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>
                                                            {{ $user->name }} kullanıcısını silmek istediğinizden emin
                                                            misiniz?
                                                        </p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">
                                                            Kapat
                                                        </button>
                                                        <form action="{{ route('usersettiginis.destroy', $user->id) }}"
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

    <!-- Modal (Harita Seçimi) -->
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
        var map;
        var marker;
        var selectedField = null;
        var mapsLoaded = false;

        // Google Maps yüklenince çağrılacak callback
        function initMap() {
            console.log("Google Maps API loaded.");
            mapsLoaded = true;
        }

        // Konum Seç Butonuna Tıklayınca
        function openMapModal(field) {
            if (!mapsLoaded) {
                alert("Harita henüz yüklenmedi. Lütfen sayfayı yenileyin veya biraz bekleyin.");
                return;
            }
            selectedField = field;

            // Mevcut lat,lng
            var val = document.getElementById(field).value;
            var lat = 37.13319;
            var lng = 38.740342;
            if (val && val.includes(',')) {
                var parts = val.split(',');
                lat = parseFloat(parts[0]);
                lng = parseFloat(parts[1]);
            }

            // Modal elemanını al
            var mapModalEl = document.getElementById('mapModal');
            // Bootstrap modal'ı aç
            var myModal = new bootstrap.Modal(mapModalEl, {
                keyboard: false
            });
            myModal.show();

            // Modal tamamen gösterildiğinde haritayı init edelim
            mapModalEl.addEventListener('shown.bs.modal', function handler() {
                // Bu eventin her açılışta 2 defa tetiklenmesini önlemek için:
                mapModalEl.removeEventListener('shown.bs.modal', handler);

                initModalMap(lat, lng);
            });
        }

        // Haritayı Initialize Et
        function initModalMap(lat, lng) {
            console.log("initModalMap called with lat:", lat, "lng:", lng);
            var center = {
                lat: lat,
                lng: lng
            };

            map = new google.maps.Map(document.getElementById("mapModalContainer"), {
                zoom: 14,
                center: center
            });

            marker = new google.maps.Marker({
                position: center,
                map: map,
                draggable: false
            });

            // Haritaya tıklayınca marker'ı güncelle
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

        // "Seç" butonuna tıklayınca input'a (lat,lng) yaz
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

    <!-- Burada YOUR_API_KEY kısmına kendi Google Maps API keyinizi yazın -->
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA0Sg5IHsU2_xejwA5VUGshWdhkf9EU__E&libraries=places&callback=initMap"
        async defer></script>
@endpush
