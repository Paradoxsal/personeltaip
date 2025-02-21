@extends('layouts.user_type.auth')
@section('content')
<div class="container-fluid">
    <div class="page-header min-height-300 border-radius-xl mt-4"
        style="background-image: url('../assets/img/curved-images/curved0.jpg'); background-position-y: 50%;">
        <span class="mask bg-gradient-primary opacity-6"></span>
    </div>
    <div class="card card-body blur shadow-blur mx-4 mt-n6">
        <div class="row gx-4">
            <div class="col-auto my-auto">
                <h5 class="mb-1">{{ auth()->user()->name }}</h5>
                <p class="mb-0 font-weight-bold text-sm">{{ __('Role Yetkisi') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header pb-0 px-3">
            <h6 class="mb-0">{{ __('Kullanıcı Bilgileri') }}</h6>
        </div>
        <div class="card-body pt-4 p-3">
            @if ($errors->any())
                <div class="mt-3 alert alert-primary alert-dismissible fade show" role="alert">
                    <span class="alert-text text-white">{{ $errors->first() }}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                        <i class="fa fa-close" aria-hidden="true"></i>
                    </button>
                </div>
            @endif
            @if (session('success'))
                <div class="m-3 alert alert-success alert-dismissible fade show" id="alert-success" role="alert">
                    <span class="alert-text text-white">{{ session('success') }}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                        <i class="fa fa-close" aria-hidden="true"></i>
                    </button>
                </div>
            @endif

            <form action="{{ route('updateProfile') }}" method="GET" role="form text-left">
                @csrf
                <input type="hidden" name="old_password" value="{{ $personel->password }}">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="user-name" class="form-control-label">{{ __('Ad Soyadı') }}</label>
                        <input class="form-control" value="{{ auth()->user()->name }}" type="text" id="user-name"
                            name="name">
                        @error('name')
                        <p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                    </div>


                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-control-label">{{ __('Yeni Şifreniz') }}</label>
                        <input class="form-control" value="" type="password" id="password" name="password">
                    </div>



                </div>


                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="number" class="form-control-label">{{ __('Telefon Numarası') }}</label>
                        <input class="form-control" type="tel" id="number" name="phone"
                            value="{{ auth()->user()->phone }}">
                        @error('phone')
                        <p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-control-label">{{ __('Şifre Tekrar') }}</label>
                        <input class="form-control" type="password" id="password_confirmation"
                            name="password_confirmation">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="user-email" class="form-control-label">{{ __('Email Adresi') }}</label>
                        <input class="form-control" value="{{ auth()->user()->email }}" type="email" id="user-email"
                            name="email">
                        @error('email')
                        <p class="text-danger text-xs mt-2">{{ $message }}</p>@enderror
                    </div>



                    <div class="col-md-6 mb-3">
                        <label for="check_in_location" class="form-control-label">{{ __('Giriş Konumun') }}</label>
                        <div class="input-group">
                            <input class="form-control" type="text" id="check_in_location" name="check_in_location"
                                value="{{ auth()->user()->check_in_location }}">
                            <button type="button" class="btn btn-info"
                                onclick="openMapModal('check_in_location')">{{ __('Giriş Konumunu Seç') }}</button>
                        </div>
                        @error('check_in_location')
                            <p class="text-danger text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="units_id" class="form-control-label">{{ __('Birim') }}</label>
                        <select id="units_id" name="units_id" class="form-control">
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" {{ old('units_id', $personel->units_id) == $department->id ? 'selected' : '' }}>
                                    {{ $department->unit_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="check_out_location" class="form-control-label">{{ __('Çıkış Konumun') }}</label>
                        <div class="input-group">
                            <input class="form-control" type="text" id="check_out_location" name="check_out_location"
                                value="{{ auth()->user()->check_out_location }}">

                            <button type="button" class="btn btn-info"
                                onclick="openMapModal('check_out_location')">{{ __('Çıkış Konumunu Seç') }}</button>
                        </div>
                        @error('check_out_location')
                            <p class="text-danger text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" class="btn bg-gradient-primary btn-bg mb-0">
                        +&nbsp; Kullanıcı Güncelle
                    </button>
                </div>
            </form>

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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Kapat') }}</button>
                <button type="button" class="btn btn-primary" onclick="selectLocation()">{{ __('Seç') }}</button>
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
            if (val && val.includes(',')) {
                var parts = val.split(',');
                lat = parseFloat(parts[0]);
                lng = parseFloat(parts[1]);
            }
            initModalMap(lat, lng);


            var myModal = new bootstrap.Modal(document.getElementById('mapModal'), {
                keyboard: false
            })
            myModal.show();
        }

        function initModalMap(lat, lng) {
            console.log("initModalMap called with lat:", lat, "lng:", lng);
            var center = { lat: lat, lng: lng };
            map = new google.maps.Map(document.getElementById("mapModalContainer"), {
                zoom: 14,
                center: center,
            });

            marker = new google.maps.Marker({
                position: center,
                map: map,
                draggable: false
            });

            map.addListener('click', function (e) {
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
    <!-- Kendi API keyinizi YOUR_API_KEY ile değiştirin -->
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA0Sg5IHsU2_xejwA5VUGshWdhkf9EU__E&libraries=places&callback=initMap"
        async defer></script>
@endpush